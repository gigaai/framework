<?php

namespace GigaAI\Http;

use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;

class Session
{
    use EasyCall, Singleton;

    /**
     * Get config by key
     *
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    private function get($key, $default = null)
    {
        if ( ! empty($_SESSION[$key]))
            return $_SESSION[$key];

        return $default;
    }

    /**
     * Set config
     *
     * @param mixed $key
     * @param null $value
     * @return $this
     */
    private function set($key, $value = null)
    {
        if (is_array($key) && null === $value)
        {
            foreach ($key as $k => $v)
            {
                $_SESSION[$k] = $v;
            }

            return $this;
        }

        $_SESSION[$key] = $value;

        return $this;
    }

    private function has($key)
    {
        return isset($_SESSION[$key]);
    }

    private function delete($key)
    {
        unset($_SESSION[$key]);
    }

    public static function getInstance()
    {
        if (null === static::$instance) {
            session_start();

            static::$instance = new static();
        }
        return static::$instance;
    }
}