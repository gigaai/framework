<?php

namespace GigaAI\Storage;

use GigaAI\Core\Config;
use GigaAI\Http\Request;

/**
 * Storage interacts with your client info and save it to your drivers.
 */
class Storage
{
    /**
     * Driver instance
     *
     * @var $driver
     */
    protected $driver = null;

    public function __construct()
    {
        // Create connection between GigaAI and your storage database.
        $this->createConnection();
    }

    /**
     * Load driver and then create connection
     * @param null $name
     */
    public function createConnection($name = null)
    {
        // Prevent duplicate drivers
        if (is_object($this->driver))
            return;

        $name = isset($name) ? $name : Config::get('storage_driver');

        $driver_name = $this->getDriverClassName($name);
        
        $class = __NAMESPACE__ . '\\' . $driver_name;

        if (class_exists($class) && in_array('GigaAI\Storage\StorageInterface', class_implements($class)))
            $this->driver = new $class;
    }

    private function pull($event)
    {
        $user_id = $event->sender->id;

        // Todo: Check cache time and fetch new data
        if ($this->has($user_id))
            return;

        $request = new Request;

        $profile = $request->getUserProfile($user_id);

        if (empty($profile['first_name']))
            return;

        // Parse event to array
        $profile['user_id']    = $user_id;
        $profile['subscribed'] = 1;

        // Then call set method
        $this->set($profile);
    }

    /**
     * Magic method to load storage driver methods
     *
     * @param $name
     * @param array $args
     * @return $this
     */
    public function __call($name, $args = array())
    {
        if ( $this->driver === null )
            $this->createConnection();
        
        if (method_exists($this, $name))
            return call_user_func_array(array($this, $name), $args);

        return call_user_func_array(array($this->driver, $name), $args);
    }

    /**
     * Magic method to load storage driver methods
     *
     * @param $name
     * @param array $args
     * @return $this
     */
    public static function __callStatic($name, $args = array())
    {
        $storage = new self;

        return $storage->__call($name, $args);
    }

    /**
     * Get driver class name from slug. Returns {Slug}StorageDriver
     *
     * @param String $slug Driver name. For example: file
     * @return string
     */
    private function getDriverClassName($slug)
    {
        $driver_name = $slug == 'wordpress' ? 'WordPress' : $slug;

        return ucfirst($driver_name) . 'StorageDriver';
    }
}