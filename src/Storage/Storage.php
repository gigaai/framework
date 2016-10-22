<?php

namespace GigaAI\Storage;

use GigaAI\Core\Config;
use GigaAI\Http\Request;
use GigaAI\Storage\Eloquent\Node;
use Illuminate\Database\Capsule\Manager as Capsule;
use GigaAI\Storage\Eloquent\Lead;
use GigaAI\Shared\EasyCall;

/**
 * Storage interacts with your client info and save it to your drivers.
 */
class Storage
{
    use EasyCall;

    /**
     * Builder instance
     *
     * @var $driver
     */
    protected $db = null;

    public function __construct()
    {
        $this->db = new Capsule;

        $config = Config::get('mysql');

        $this->db->addConnection([
            'driver'    => 'mysql',
            'host'      => $config['host'],
            'database'  => $config['database'],
            'username'  => $config['username'],
            'password'  => $config['password'],
            'charset'   => $config['charset'],
            'collation' => $config['collation'],
            'prefix'    => $config['prefix'],
        ]);

        // Make this Capsule instance available globally via static methods... (optional)
        $this->db->setAsGlobal();

        $this->db->bootEloquent();
    }

    private function pull($lead_id)
    {
        // Todo: Check cache time and fetch new data
        if (self::has($lead_id))
            return;

        $lead = Request::getUserProfile($lead_id);

        if (empty($lead['first_name']) || empty($lead['last_name']))
            return;

        // Parse event to array
        $lead['user_id']    = $lead_id;
        $lead['subscribe']  = 1;

        // Then call set method
        self::set($lead);
    }

    private function set($user, $key = '', $value = '')
    {
        if (is_string($user)) {
            if (is_array($key)) {
                $key['user_id'] = $user;

                return $this->set($key);
            }

            $user = [
                'user_id' => $user,
                $key      => $value
            ];
        }

        if (is_array($user) && isset($user['user_id']))
            return $this->insertOrUpdateUser($user);
    }

    private function insertOrUpdateUser($user)
    {
        $meta = [];

        foreach ($user as $key => $value) {
            if (!in_array($key, (new Lead)->getFillable())) {
                $meta[$key] = $value;

                unset($user[$key]);
            }
        }
        try {

            $lead = Lead::updateOrCreate([
                'source' => 'facebook',
                'user_id' => $user['user_id']
            ], $user);
        } catch (\PDOException $pe) {
            echo '<pre>';
            dd($pe);
        }
        if (!empty($meta)) {
            foreach ($meta as $key => $value) {
                $this->db->table('bot_leads_meta')->updateOrCreate([
                    'user_id' => $lead->user_id,
                    'meta_key' => $key
                ], [
                    'meta_value' => $value
                ]);
            }
        }
    }

    private function has($user_id, $key = '')
    {
        $user = $this->getUser($user_id);

        return $user || !empty($user[$key]);
    }

    private function getUser($user_id)
    {
        $user = Lead::where([
            'source' => 'facebook',
            'user_id' => $user_id
        ])->first();

        if ( ! is_null($user))
            return $user->toArray();

        return null;
    }

    /**
     * Get User Info. If provided user
     *
     * @param string $user_id If not provided, load all users. Otherwise, load specified user.
     * @param string $key If not provided. load all fields. Otherwise, load specified field.
     * @param mixed $default Default value.
     *
     * @return bool|null|string
     */
    private function get($user_id = '', $key = '', $default = '')
    {
        $user = $this->getUser($user_id);

        if (is_null($user))
            return null;

        if ( ! empty($key)) {
            if (isset($user[$key]))
                return $user[$key];

            if ( ! in_array($key, (new Lead)->getFillable()))
                return $this->getUserMeta($user_id, $key, $default);

            return $default;
        }

        return $user;
    }

    private function getUserMeta($user_id, $key = '', $default = '')
    {
        $meta = $this->db->table('bot_leads_meta')->where([
            'user_id' => $user_id,
            'meta_key' => $key
        ])->first();

        if ( ! is_null($meta))
            return $meta->meta_value;

        return $default;
    }


    /**
     * Search in collection
     *
     * @param $terms
     * @param string $relation
     * @return mixed
     */
    private function search($terms, $relation = 'and')
    {
        return Lead::where($terms)->get();
    }

    /**
     * Add Answer to the database
     *
     * @param $answer
     * @param $node_type
     * @param string $ask
     *
     * @return Node
     */
    private function addNode($answers, $node_type, $ask = '')
    {
        $node = Node::where(['type' => $node_type, 'pattern' => $ask])->first();

        if (is_null($node)) {
            $node = Node::create([
                'type'      => $node_type,
                'pattern'   => $ask,
                'answers'   => $answers
            ]);
        } else {
            $node->answers = $answers;

            $node->save();
        }

        return $node;
    }

    private function removeNode($node_type, $ask)
    {
        Node::where([
            'type'      => $node_type,
            'pattern'   => $ask
        ])->delete();
    }

    private function removeNodeById($id)
    {
        Node::destroy($id);
    }
}