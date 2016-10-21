<?php

namespace GigaAI\Conversation;

use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;

class Conversation
{
    use EasyCall, Singleton;

    private $shared;

    /**
     * Get config by key
     *
     * @param $key
     * @param null $default
     * @return mixed|null
     */
    private function get($key, $default = null)
    {
        if ( ! empty($this->shared[$key]))
            return $this->shared[$key];

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
                $this->shared[$k] = $v;
            }

            return $this;
        }

        $this->shared[$key] = $value;

        return $this;
    }

    private function has($key)
    {
        return isset($this->shared[$key]);
    }

    private function delete($key)
    {
        unset($this->shared[$key]);
    }
}