<?php


namespace GigaAI\Messages;


/**
 * Class TextMessage
 *
 * @package GigaAI\Messages
 */
class TextMessage extends AbstractMessage
{
    private $text;

    /**
     * TextMessage constructor.
     *
     * @param $recipient
     * @param $text
     */
    public function __construct($recipient, $text)
    {
        parent::__construct($recipient);

        $this->text = $text;
    }

    /**
     * Return body path of the message
     * Text message has format:
     * "message":{
     *     "text":"hello, world!"
     *  }
     *
     * @return mixed
     */
    public function getMessageBody()
    {
        return [
            'text' => $this->text
        ];
    }
}