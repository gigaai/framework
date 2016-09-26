<?php


namespace GigaAI\Core\Responders;


/**
 * Interface MessageResponderInterface
 *
 * @package GigaAI\Core\Responders
 */
interface MessageResponderInterface
{
    /**
     * Make a new Message from $input & list of rules
     *
     * @param $recipient
     * @param $input
     *
     * @return mixed
     */
    public function response($recipient, $input);
}