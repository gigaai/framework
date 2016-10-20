<?php

namespace GigaAI\Shared;

trait EasyCall
{
    public function __call($name, $args = array())
    {
        return call_user_func_array(array($this, $name), $args);
    }

    public static function __callStatic($name, $args = array())
    {
        if (method_exists( __CLASS__, 'getInstance')) {
            return self::getInstance()->__call($name, $args);
        }

        $self = new self;

        return $self->__call($name, $args);
    }
}