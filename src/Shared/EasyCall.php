<?php

namespace GigaAI\Shared;

/**
 * EasyCall trait lets you call the class method with both static and non static way
 *
 * @package GigaAI\Shared
 */
trait EasyCall
{
    /**
     * When you call non static
     *
     * @param $name
     * @param array $args
     * @return mixed
     */
    public function __call($name, $args = [])
    {
        return call_user_func_array([$this, $name], $args);
    }

    /**
     * When you call static
     *
     * @param $name
     * @param array $args
     * @return mixed
     */
    public static function __callStatic($name, $args = [])
    {
        // Check if class is single ton
        if (method_exists(__CLASS__, 'getInstance')) {
            return self::getInstance()->__call($name, $args);
        }

        $self = new self;
        return $self->__call($name, $args);
    }
}