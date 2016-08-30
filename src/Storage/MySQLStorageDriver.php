<?php
/**
 * MySQL Storage Driver
 *
 * This driver requires you install `illuminate/database` package and PHP 5.4+
 *
 * @author Gary <gary@binaty.org>
 */
namespace GigaAI\Storage;

use GigaAI\Core\Config;
use Illuminate\Database\Capsule\Manager as Capsule;
use GigaAI\Storage\Eloquent\Lead;
use GigaAI\Storage\Eloquent\Answer;

class MySQLStorageDriver implements StorageInterface
{
    private $db;

    /**
     * MySQLStorageDriver constructor.
     *
     * Setup the connection
     */
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

    public function set($user, $key = '', $value = '')
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
        $meta = array();

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
                $this->db->table('bot_leads_meta')->updateOrInsert([
                    'user_id' => $lead->user_id,
                    'meta_key' => $key
                ], [
                    'meta_value' => $value
                ]);
            }
        }
    }

    public function has($user_id, $key = '')
    {
        $user = $this->getUser($user_id);

        return $user || !empty($user[$key]);
    }

    public function getUser($user_id)
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
    public function get($user_id = '', $key = '', $default = '')
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

    public function getUserMeta($user_id, $key = '', $default = '')
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
    public function search($terms, $relation = 'and')
    {
        return Lead::where($terms)->get();
    }

    private function searchInCache($node_type, $ask = '')
    {
        $cache_file = Config::get('cache_path') . 'answers.json';

        $answers = json_decode(file_get_contents($cache_file));

        return array_filter($answers, function ($record) use ($node_type, $ask) {
            return $record->type === $node_type && $record->ask === $ask;
        });
    }

    /**
     * Add Answer to the database
     *
     * @param $answer
     * @param $node_type
     * @param string $ask
     */
    public function addAnswer($answers, $node_type, $ask = '')
    {
        $row = Answer::where(['type' => $node_type, 'pattern' => $ask])->first();

        if (is_null($row)) {
            Answer::create([
                'type'      => $node_type,
                'pattern'   => $ask,
                'answers'   => $answers
            ]);
        } else {
            $row->answers = $answers;

            $row->save();
        }
    }

    public function getAnswers($node_type = '', $ask = '')
    {
        $where = '1 = 1';
        $placeholder = [];

        if ( ! empty($node_type)) {
            $where .= ' AND type = :type';
            $placeholder[':type'] = $node_type;
        }
        if ( ! empty($ask)) {
            $where .= " AND (:ask RLIKE pattern OR :ask2 LIKE pattern)";
            $placeholder[':ask']    = $ask;
            $placeholder[':ask2']   = $ask;
        }

        $answers = Answer::whereRaw($where, $placeholder)
            ->get(['type', 'pattern', 'answers'])
            ->toArray();

        $output = [];

        foreach ($answers as $answer) {

            // If default, then return only first row fetched!
            if ($node_type === 'default' && $answer['type'] === 'default')
                return ['default' => $answer['answers']];

            if ( ! isset($output[$answer['type']]))
                $output[$answer['type']] = [];

            if ( ! isset($output[$answer['type']][$answer['pattern']]))
                $output[$answer['type']][$answer['pattern']] = [];

            $output[$answer['type']][$answer['pattern']] = $answer['answers'];
        }

        return $output;
    }

    public function removeAnswers($node_type, $ask)
    {
        Answer::where([
            'type'      => $node_type,
            'pattern'   => $ask
        ])->delete();
    }
}