<?php


namespace GigaAI\Core\Responders;


use GigaAI\Core\Rule\Rule;


/**
 * Interface MessageResponderInterface
 *
 * @package GigaAI\Core\Responders
 */
interface MessageResponderInterface
{

    /**
     * Set rules for responder
     *
     * @param Rule[] $rules
     *
     * @return mixed
     */
    public function setRules($rules = []);

    /**
     * Make a new Message from $input & list of rules
     *
     * @param $recipient
     * @param $input
     *
     * @return array
     */
    public function response($recipient, $input);

    /**
     * Response a message to user and then continue waiting user's response
     *
     * @param $messageRule
     *
     * @return array
     */
    public function responseFail($messageRule);
}