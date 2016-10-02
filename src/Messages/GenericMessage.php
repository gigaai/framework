<?php


namespace GigaAI\Messages;


/**
 * Class GenericMessage
 *
 * @package GigaAI\Messages
 */
class GenericMessage extends AbstractMessage
{
    private $elements = [];

    /**
     * ButtonMessage constructor.
     *
     * @param $recipient
     * @param $elements
     */
    public function __construct($recipient, $elements)
    {
        parent::__construct($recipient);

        $this->elements = $elements;
    }

    /**
     * Return body path of the message
     * Button message format:
     * "message":{
     *    "attachment":{
     *       "type":"template",
     *       "payload":{
     *         "template_type":"generic",
     *         "elements":[
     *           {
     *             "title":"Welcome to Peter\'s Hats",
     *             "item_url":"https://petersfancybrownhats.com",
     *             "image_url":"https://petersfancybrownhats.com/company_image.png",
     *             "subtitle":"We\'ve got the right hat for everyone.",
     *             "buttons":[
     *               {
     *                 "type":"web_url",
     *                 "url":"https://petersfancybrownhats.com",
     *                 "title":"View Website"
     *               },
     *               {
     *                 "type":"postback",
     *                 "title":"Start Chatting",
     *                 "payload":"DEVELOPER_DEFINED_PAYLOAD"
     *               }
     *             }
     *           }
     *         }
     *      }
     *  }
     *
     * @return mixed
     */
    function getMessageBody()
    {
        return [
            'attachment' => [
                'type' => 'template',
                'payload' => [
                    'template_type' => 'generic',
                    'elements' => $this->elements
                ]
            ]
        ];
    }
}