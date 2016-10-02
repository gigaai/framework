<?php


namespace GigaAI\Messages;


/**
 * Class AudioAttachmentMessage
 *
 * @package GigaAI\Messages
 */
class AudioAttachmentMessage extends AbstractMessage
{
    private $url;

    /**
     * AudioAttachmentMessage constructor.
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
     * Video attachment format:
     *   "message":{
     *      "attachment":{
     *        "type":"audio",
     *        "payload":{
     *          "url":"https://petersapparel.com/bin/clip.mp3"
     *        }
     *      }
     *  }
     *
     * @return mixed
     */
    function getMessageBody()
    {
        return [
            'attachment' => [
                'type' => 'audio',
                'payload' => [
                    'url' => $this->url
                ]
            ]
        ];
    }
}