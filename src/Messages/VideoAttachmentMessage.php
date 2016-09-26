<?php


namespace GigaAI\Messages;


/**
 * Class VideoAttachmentMessage
 *
 * @package GigaAI\Messages
 */
class VideoAttachmentMessage extends AbstractMessage
{
    private $url;

    /**
     * VideoAttachmentMessage constructor.
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
     *        "type":"video",
     *        "payload":{
     *          "url":"https://petersapparel.com/bin/clip.mp4"
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
                'type' => 'video',
                'payload' => [
                    'url' => $this->url
                ]
            ]
        ];
    }
}