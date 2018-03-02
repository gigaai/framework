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

        if ( ! defined('DB_HOST')) {
            return;
        }

        $this->createConfigFromWordPress();

        $config = Config::get('mysql');

        $connection = [
            'driver'    => 'mysql',
            'host'      => $config['host'],
            'database'  => $config['database'],
            'username'  => $config['username'],
            'password'  => $config['password'],
            'charset'   => 'utf8mb4',
            'collation' => 'utf8mb4_unicode_ci',
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

    private function isSupportedMySQLVersion()
    {
        $supports = [
            'mysql' => '5.7.0',
            'maria' => '10.2',
        ];

        $type = 'mysql';

        $query = Capsule::select(
            Capsule::raw('SELECT version() as version')
        );

        $mysql_version = $query[0]->version;

        preg_match("/^[0-9\.]+/", $mysql_version, $match);

        $version = $match[0];

        if (strpos($mysql_version, 'Maria') !== false) {
            $type = 'maria';
        }

        $supported = (version_compare($version, $supports[$type]) >= 0);

        return $supported;
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
                'collation' => 'utf8mb4_unicode_ci',
                'prefix'    => $wpdb->prefix,
            ];

            Config::set('mysql', $mysql);
        }
    }

    private function pull()
    {
        $lead_id    = Conversation::get('lead_id');
        $lead       = Lead::withTrashed()->where('user_id', $lead_id)->first();

        if ($lead !== null) {
            return $lead;
        }

        $lead = Request::getUserProfile($lead_id);

        if (empty($lead['first_name']) || empty($lead['last_name'])) {
            return;
        }

        // Parse event to array
        $lead['user_id']   = isset($lead['id']) ? $lead['id'] : $lead_id;
        $lead['subscribe'] = 1;
        $lead['source']    = isset($lead['source']) ? $lead['source'] : Conversation::get('page_id');

        $lead = Lead::updateOrCreate([
            'user_id' => $lead['user_id'],
        ], $lead);

        sd($lead);

        return $lead;
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
                if (!in_array($key, ['type', 'pattern', 'answers'])) {
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
