<?php


namespace GigaAI\Messages;


/**
 * Class AbstractMessage
 *
 * @package GigaAI\Messages
 */
abstract class AbstractMessage
{
    private $recipient;

    /**
     * AbstractMessage constructor.
     *
     * @param $recipient
     */
    public function __construct($recipient)
    {
        $this->recipient = $recipient;
    }

    /**
     * Return body path of the message
     *
     * @return mixed
     */
    abstract function getMessageBody();

    /**
     * Combine recipient & message body to an array
     *
     * @return array
     */
    public function getRawMessage()
    {
        return [
            'recipient' => [
                'id' => $this->recipient
            ],
            'message' => $this->getMessageBody()
        ];

    }
}