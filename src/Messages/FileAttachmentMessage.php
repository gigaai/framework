<?php


namespace GigaAI\Messages;


/**
 * Class FileAttachmentMessage
 *
 * @package GigaAI\Messages
 */
class FileAttachmentMessage extends AbstractMessage
{
    private $url;

    /**
     * FileAttachmentMessage constructor.
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
     * FileAttachment message format:
     * "message":{
     *    "attachment":{
     *       "type":"file",
     *       "payload":{
     *           "url":"https://petersapparel.com/bin/receipt.pdf"
     *       }
     *    }
     * }
     *
     * @return mixed
     */
    function getMessageBody()
    {
        return [
            'attachment' => [
                'type' => 'file',
                'payload' => [
                    'url' => $this->url
                ]
            ]
        ];
    }

}