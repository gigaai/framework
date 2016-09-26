<?php


namespace GigaAI\Messages;


/**
 * Class ImageMessage
 *
 * @package GigaAI\Messages
 */
class ImageMessage extends AbstractMessage
{
    private $url;

    /**
     * ImageMessage constructor.
     *
     * @param $recipient
     * @param $url
     */
    public function __construct($recipient, $url)
    {
        parent::__construct($recipient);

        $this->url = $url;
    }

    /**
     * Return body path of the message
     * Image message has format:
     * "message":{
     *    "attachment":{
     *        "type":"image",
     *        "payload":{
     *            "url":"https://petersapparel.com/img/shirt.png"
     *        }
     *      }
     *   }
     *
     * @return mixed
     */
    function getMessageBody()
    {
        return [
            'attachment' => [
                'type' => 'image',
                'payload' => [
                    'url' => $this->url
                ]
            ]
        ];
    }
}