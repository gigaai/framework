<?php


namespace GigaAI\Core\Responders;


use GigaAI\Messages\AbstractMessage;
use GigaAI\Messages\ButtonMessage;
use GigaAI\Messages\FileAttachmentMessage;
use GigaAI\Messages\ImageMessage;
use GigaAI\Messages\TextMessage;
use GigaAI\Messages\VideoAttachmentMessage;


/**
 * Class DefaultMessageResponder
 *
 * @package GigaAI\Core\Responders
 */
class DefaultMessageResponder extends AbstractMessageResponder
{
    /**
     * @param $recipient
     * @param $input
     *
     * @return AbstractMessage
     */
    public function response($recipient, $input)
    {
//        return new VideoAttachmentMessage($recipient, 'http://www.sample-videos.com/video/mp4/720/big_buck_bunny_720p_1mb.mp4');
        $buttons = [
            [
                "type" => "web_url",
                "url" => "https://petersapparel.parseapp.com",
                "title" => "Show Website"
            ],
            [
                "type" => "postback",
                "title" => "Start Chatting",
                "payload" => "USER_DEFINED_PAYLOAD"
            ]
        ];
        return new ButtonMessage($recipient, 'This is a test button', $buttons);
//        return new FileAttachmentMessage($recipient, 'http://genknews.genkcdn.vn/zoom/230_180/2016/45pskyxk-1465186800-1466518839281.jpg');
//        return new ImageMessage($recipient, 'http://genknews.genkcdn.vn/zoom/230_180/2016/45pskyxk-1465186800-1466518839281.jpg');
//        return new TextMessage($recipient, 'hello');
    }
}