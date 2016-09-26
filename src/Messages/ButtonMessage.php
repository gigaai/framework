<?php


namespace GigaAI\Messages;


/**
 * Class ButtonMessage
 *
 * @package GigaAI\Messages
 */
class ButtonMessage extends AbstractMessage
{
    private $text;

    private $buttons = [];

    /**
     * ButtonMessage constructor.
     *
     * @param $recipient
     * @param $text
     * @param $buttons
     */
    public function __construct($recipient, $text, $buttons)
    {
        parent::__construct($recipient);

        $this->text = $text;
        $this->buttons = $buttons;
    }

    /**
     * Return body path of the message
     * Button message format:
     * "message":{
     *   "attachment":{
     *     "type":"template",
     *     "payload":{
     *       "template_type":"button",
     *       "text":"What do you want to do next?",
     *       "buttons":[
     *         {
     *           "type":"web_url",
     *           "url":"https://petersapparel.parseapp.com",
     *           "title":"Show Website"
     *         },
     *         {
     *           "type":"postback",
     *           "title":"Start Chatting",
     *           "payload":"USER_DEFINED_PAYLOAD"
     *         }
     *       ]
     *     }
     *   }
     * }
     *
     * @return mixed
     */
    function getMessageBody()
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'button',
                    'text' => $this->text,
                    'buttons' => $this->buttons
                ]
            ]
        ];
    }
}