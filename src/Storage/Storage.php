<?php

namespace GigaAI\Storage;

use GigaAI\Conversation\Conversation;
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
        // Create connection if we run outside Laravel
        $this->createConnection();
    }
    
    private function createConnection()
    {
        $this->db = new Capsule;
        
        $this->createConfigFromLaravel();
        
        $this->createConfigFromWordPress();
        
        $config = Config::get('mysql');
        
        $connection = [
            'driver'   => 'mysql',
            'host'     => $config['host'],
            'database' => $config['database'],
            'username' => $config['username'],
            'password' => $config['password'],
        ];
        
        foreach (['charset', 'collation', 'prefix'] as $optional) {
            if (isset($config[$optional])) {
                $connection[$optional] = $config[$optional];
            }
        }
        
        $this->db->addConnection($connection);
        
        // Make this Capsule instance available globally via static methods... (optional)
        $this->db->setAsGlobal();
        
        $this->db->bootEloquent();
    }
    
    private function createConfigFromWordPress()
    {
        if (defined('DB_HOST') && defined('DB_NAME') && defined('DB_USER') && defined('DB_PASSWORD') && defined('DB_CHARSET')) {
            global $wpdb;
            
            $mysql = [
                'host'      => DB_HOST,
                'database'  => DB_NAME,
                'username'  => DB_USER,
                'password'  => DB_PASSWORD,
                'charset'   => DB_CHARSET,
                'collation' => null,
                'prefix'    => $wpdb->prefix,
            ];
            
            Config::set('mysql', $mysql);
        }
    }
    
    private function createConfigFromLaravel()
    {
        if ( ! function_exists('app')) {
            return;
        }
        
        $mysql = [
            'host'      => env('DB_HOST'),
            'database'  => env('DB_DATABASE'),
            'username'  => env('DB_USERNAME'),
            'password'  => env('DB_PASSWORD'),
            'collation' => null,
        ];
        
        Config::set('mysql', $mysql);
    }
    
    private function pull()
    {
        $lead_id = Conversation::get('lead_id');
        
        $lead = self::getUser($lead_id);
        
        if ( ! empty($lead) || ! is_null($lead)) {
            return;
        }
        
        $lead = Request::getUserProfile($lead_id);
        
        if (empty($lead['first_name']) || empty($lead['last_name'])) {
            return;
        }
        
        // Parse event to array
        $lead['user_id']    = isset($lead['user_id']) ? $lead['user_id'] : $lead_id;
        $lead['subscribe']  = 1;
        $lead['source']     = isset($lead['source']) ? $lead['source'] : Conversation::get('page_id');
        
        // Then call set method
        self::set($lead);
    }
    
    private function set($user, $key = '', $value = '')
    {
        if ( ! is_array($user)) {
            if (is_array($key)) {
                $key['user_id'] = $user;
                
                return $this->set($key);
            }
            
            $user = [
                'user_id' => $user,
                $key      => $value,
            ];
        }

        if (is_array($user) && isset($user['user_id'])) {
            return $this->insertOrUpdateUser($user);
        }
    }
    
    /**
     * Insert if user has already exists or update if exists
     * 
     * @param Array $user
     * 
     * @return void
     */
    private function insertOrUpdateUser($user)
    {
        $meta = [];
        
        foreach ($user as $key => $value) {
            if ( ! in_array($key, (new Lead)->getFillable())) {
                $meta[$key] = $value;
                
                unset($user[$key]);
            }
        }
        try {
            $lead = Lead::updateOrCreate([
                'user_id' => $user['user_id'],
            ], $user);
        } catch (\PDOException $pe) {
            echo '<pre>';
            dd($pe);
        }
        if ( ! empty($meta)) {
            $this->updateLeadMeta($lead->user_id, $meta);
        }
    }
    
    /**
     * Check if current user has given field
     *
     * @param Int $user_id
     * @param string $key
     * @return boolean
     */
    private function has($user_id, $key = '')
    {
        $user = $this->getUser($user_id);
        
        return $user || ! empty($user[$key]);
    }
    
    /**
     * Get User by Facebook app scoped ID
     * 
     * @param String $user_id App Scoped Id
     * 
     * @return Mixed
     */
    private function getUser($user_id)
    {
        $user = Lead::where([
            'user_id' => $user_id,
        ])->first();
        
        if ( ! is_null($user)) {
            return $user->toArray();
        }
        
        return null;
    }
    
    /**
     * Get User Info. If provided user
     *
     * @param string $user_id If not provided, load all users. Otherwise, load specified user.
     * @param string $key     If not provided. load all fields. Otherwise, load specified field.
     * @param mixed  $default Default value.
     *
     * @return bool|null|string
     */
    private function get($user_id = '', $key = '', $default = '')
    {
        $user = $this->getUser($user_id);
        
        if (is_null($user)) {
            return null;
        }
        
        if ( ! empty($key)) {
            if (isset($user[$key])) {
                return $user[$key];
            }
            
            if ( ! in_array($key, (new Lead)->getFillable())) {
                return $this->getUserMeta($user_id, $key, $default);
            }
            
            return $default;
        }
        
        return $user;
    }
    
    /**
     * Get meta data of current user
     *
     * @param Int $user_id
     * @param string $key
     * @param string $default
     * 
     * @return Mixed
     */
    private function getUserMeta($user_id, $key = '', $default = '')
    {
        $where = [
            'user_id' => $user_id,
        ];
        
        if ( ! empty($key)) {
            $where['meta_key'] = $key;
            
            $meta = $this->db->table('bot_leads_meta')->where($where)->first();
            if ( ! is_null($meta)) {
                return $meta->meta_value;
            }
            
            return $default;
        } else {
            $rows = $this->db->table('bot_leads_meta')->where($where)->get();
            $meta = [];
            
            foreach ($rows as $row) {
                $meta[$row->meta_key] = $row->meta_value;
            }
            
            return $meta;
        }
    }
    
    /**
     * Todo: Optimize this method
     *
     * @param       $lead_id
     * @param array $key
     * @param null  $value
     */
    private function updateLeadMeta($lead_id, $key = [], $value = null)
    {
        $meta = [];
        
        if (is_string($key) && ! empty($value)) {
            $meta[$key] = $value;
        } else {
            $meta = $key;
        }
        
        // Is Lead Exists
        $exists = Lead::where('user_id', $lead_id)->exists();
        
        if ( ! $exists) {
            return;
        }
        
        foreach ($meta as $key => $value) {
            $exists = $this->db->table('bot_leads_meta')->where([
                'user_id'  => $lead_id,
                'meta_key' => $key,
            ])->exists();
            
            if ($exists) {
                $this->db->table('bot_leads_meta')->where([
                    'user_id'  => $lead_id,
                    'meta_key' => $key,
                ])->update([
                    'meta_value' => $value,
                ]);
            } else {
                $this->db->table('bot_leads_meta')->insert([
                    'user_id'    => $lead_id,
                    'meta_key'   => $key,
                    'meta_value' => $value,
                ]);
            }
        }
    }
    
    /**
     * Search in collection
     *
     * @param        $terms
     * @param string $relation
     *
     * @return mixed
     */
    private function search($terms, $relation = 'and')
    {
        return Lead::where($terms)->get();
    }
    
    /**
     * Add Answer to the database
     *
     * @param mixed  $answers
     * @param string $node_type
     * @param string $ask
     * @param array  $attributes
     *
     * @return Node
     */
    private function addNode($answers, $node_type, $ask = '', array $attributes = [])
    {
        $node = Node::where(['type' => $node_type, 'pattern' => $ask])->first();
        
        if (is_null($node)) {
            $node = Node::create(array_merge([
                'type'    => $node_type,
                'pattern' => $ask,
                'answers' => $answers,
            ], $attributes));
        } else {
            $node->answers = $answers;
            
            // Allows people set attributes
            foreach ($attributes as $key => $value) {
                if ( ! in_array($key, ['type', 'pattern', 'answers'])) {
                    $node->$key = $value;
                }
            }
            
            $node->save();
        }
        
        return $node;
    }
    
    private function removeNode($node_type, $ask)
    {
        Node::where([
            'type'    => $node_type,
            'pattern' => $ask,
        ])->delete();
    }
    
    private function removeNodeById($id)
    {
        Node::destroy($id);
    }
}