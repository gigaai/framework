<?php
/**
 * Singleton Configuration Class
 *
 * Class Config
 *
 * @package GigaAI\Core
 */

namespace GigaAI\Core;

use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;

class Config
{
    use Singleton, EasyCall;
    
    private $config = [];
    
    /**
     * Get config by key
     *
     * @param      $key
     * @param null $default
     *
     * @return mixed|null
     */
    private function get($key, $default = null)
    {
        if ( ! empty($this->config[$key])) {
            return $this->config[$key];
        }
        
        return $default;
    }
    
    /**
     * Set config
     *
     * @param mixed $key
     * @param null  $value
     *
     * @return $this
     */
    private function set($key, $value = null)
    {
        if (is_array($key) && null === $value) {
            foreach ($key as $k => $v) {
                $this->config[$k] = $v;
            }
            
            return $this;
        }
        
        $this->config[$key] = $value;
        
        return $this;
    }
    
    /**
     * Load configuration from file. The file should returns an array of settings.
     *
     * @param String $path File path
     *
     * @return $this|void
     */
    private function loadFromFile($path)
    {
        if ( ! is_file($path)) {
            return;
        }
        
        $config = require_once $path;
        
        if (is_array($config)) {
            $this->config = $config;
        }
    }
}