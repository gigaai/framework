<?php

namespace GigaAI\Message;

abstract class AbstractMessage
{
    public $body;

    public function __construct($body)
    {
        $this->body = $body;
    }

    /**
     * Child class should override this method
     *
     * @return array
     **/
    abstract function normalize();

    /**
     * Expected format to be parsed
     * @return bool
     */
    abstract function expectedFormat();

    /**
     * Load the message to parse and return
     *
     * @param $body
     * @return mixed
     */
    public static function load($body)
    {
        $instance = new static($body);

        if ( ! $instance->expectedFormat()) {
            return false;
        }

        return $instance->normalize();
    }
}