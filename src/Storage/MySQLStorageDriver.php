<?php

namespace GigaAI\Storage;

use GigaAI\Core\Config;
use Illuminate\Database\Capsule\Manager as Capsule;
use GigaAI\Storage\Eloquent\Lead;
use GigaAI\Storage\Eloquent\LeadMeta;

class MySQLStorageDriver implements StorageInterface
{
    private $db;

    protected $fillable = ['source', 'user_id', 'first_name', 'last_name', 'profile_pic',
        'locale', 'timezone', 'gender', 'email', 'phone', 'country', 'location', 'wait',
        'linked_account', 'subscribe', 'auto_stop'];

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
        if (is_string($user))
        {
            if (is_array($key)) {
                $key['user_id'] = $user;

                return $this->set($key);
            }

            $user = array(
                'user_id' => $user,
                $key => $value
            );
        }

        if (is_array($user) && isset($user['user_id']))
            return $this->insertOrUpdateUser($user);
    }

    private function insertOrUpdateUser($user)
    {
        $meta = array();

        foreach ($user as $key => $value)
        {
            if ( ! in_array($key, $this->fillable)) {
                $meta[$key] = $value;

                unset($user[$key]);
            }
        }

        $lead = $this->getUser($user['user_id']);

        if ( ! $lead)
            $lead = Lead::insert($user);
        else
            Lead::find($lead->id)->update($user);

        if ( ! empty( $meta ))
        {
            foreach ($meta as $key => $value)
            {
                $this->db->table('bot_leads_meta')->updateOrInsert([
                    'lead_id' => $lead->id,
                    'meta_key' => $key
                ],[
                    'meta_value' => $value
                ]);
            }
        }
    }

    public function has($user_id, $key = '')
    {
        $user = $this->getUser($user_id);

        return $user || ! empty($user[$key]);
    }

    public function getUser($user_id)
    {
        return Lead::where('source', 'facebook')
                    ->where('user_id', $user_id)
                    ->first();
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

    }

    public function addAnswer($answer, $node_type, $ask = '')
    {

    }

    public function getAnswers($node_type, $ask = '')
    {

    }
}