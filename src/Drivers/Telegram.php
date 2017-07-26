<?php

namespace GigaAI\Drivers;

use GigaAI\Conversation\Conversation;
use GigaAI\Core\Config;

/**
 * Telegram Driver
 * 
 * Because the framework is working with Facebook the best. You'll need to
 * convert Telegram request to Facebook request to let the framework parsing 
 * and returning the data and then, convert back to Telegram format.
 * 
 * @since 2.4
 */
class Telegram
{    
    /**
     * Telegram Endpoint
     * 
     * @var String
     */
    private $resource = null;

    /**
     * Access Token
     * 
     * @var String
     */
    private $token = null;

    /**
     * Set the endpoint to sending the request
     * 
     * @return void
     */
    public function __construct()
    {
        $token = Config::get('messenger.telegram_token');

        $this->resource = "https://api.telegram.org/bot{$token}/";
    }
    /**
     * Expected format to be sent from Telegram. This lets the framework detect your driver.
     *
     * @param Array $request
     * 
     * @return bool
     */
    public function expectedFormat($request)
    {
        // Telegram has update_id parameter
        return isset($request->update_id);
    }

    /**
     * Convert Telegram to Facebook request
     * 
     * @see https://core.telegram.org/bots/api
     * @see https://developers.facebook.com/docs/messenger-platform/webhook-reference#format
     */
    public function formatIncomingRequest($telegram)
    {
        if ( ! empty($telegram) && is_array($telegram)) {
            
            $sender_id = null;
            $time = null;

            if (isset($telegram['callback_query'])) {
                $message    = $telegram['callback_query'];
                $sender_id  = $telegram['callback_query']['from']['id'];
                $time       = $telegram['callback_query']['message']['date'];
            } else {
                $sender_id = $telegram['message']['from']['id'];
                $time = $telegram['message']['date'];
            }

            $facebook = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => rand(),
                        'time' => $time,
                        'messaging' => [
                            [
                                'sender' => [
                                    'id' => $sender_id,
                                ],
                                'recipient' => [
                                    'id' => rand()
                                ],
                                'timestamp' => $time,
                            ]
                        ]
                    ]
                ]
            ];

            if ( ! empty($telegram['message']['text'])) {
                $facebook['entry'][0]['messaging'][0]['message'] = [
                    'text' => $telegram['message']['text']
                ];
            }

            if ( ! empty($telegram['callback_query']['data'])) {
                $facebook['entry'][0]['messaging'][0]['postback'] = [
                    'payload' => $telegram['callback_query']['data']
                ];
            }

            return $facebook;
        }

        return null;
    }

    /**
     * Convert Facebook request back to Telegram request
     * 
     * @param $body Body as Facebook format
     */
    public function sendMessage($body)
    {
        $action = 'sendMessage';
        
        $telegram = [
            'chat_id' => $body['recipient']['id']
        ];

        $message = $body['message'];

        // Send Text
        if (isset($message['text'])) {
            $telegram['text'] = $message['text'];
        }

        // Sending Attachment
        if (isset($message['attachment']['type'])) {

             // Send Audio, Video, Image, File
            $convert = [
                'image' => 'photo',
                'audio' => 'audio',
                'video' => 'video',
                'file'  => 'document'
            ];

            foreach ($convert as $facebook_template => $telegram_template)
            {
                if ($message['attachment']['type'] === $facebook_template) {
                    $telegram[$telegram_template] = $message['attachment']['payload']['url'];
                    $action = 'send' . ucfirst($telegram_template);
                }
            }
            
            // Sending Button
            // Facebook Button will be converted to InlineKeyboard
            if ($message['attachment']['type'] === 'template' && 
                isset($message['attachment']['payload']) &&
                $message['attachment']['payload']['template_type'] === 'button'
            ) {
                $telegram['text'] = $message['attachment']['payload']['text'];
                $telegram['reply_markup'] = [
                    'inline_keyboard' => []
                ];

                foreach ($message['attachment']['payload']['buttons'] as $button) {
                    $telegram_button = [
                        'text' => $button['title']
                    ];

                    if (isset($button['type']) && $button['type'] === 'web_url') {
                        $telegram_button['url'] = $button['url'];
                    }

                    if (isset($button['type']) && $button['type'] === 'postback') {
                        $telegram_button['callback_data'] = $button['payload'];
                    }

                    $telegram['reply_markup']['inline_keyboard'][] = [
                        $telegram_button
                    ];
                }
            }
        }

        // Facebook Quick Replies will be converted to ReplyKeyboardMarkup
        if ( ! empty($message['quick_replies'])) {
            $telegram['reply_markup'] = [
                'keyboard' => [],
                'one_time_keyboard' => true,
            ];

            foreach ($message['quick_replies'] as $reply) {
                if ($reply['content_type'] === 'text') {
                    $telegram['reply_markup']['keyboard'][] = [
                        [
                            'text'  => $reply['title']
                        ]
                    ];
                }

                if ($reply['content_type'] === 'location') {
                    $telegram['reply_markup']['keyboard'][] = [
                        [
                            'text'              => 'Send Location',
                            'request_location'  => true
                        ]
                    ];
                }
            }
        }

        // We have to json encode the reply_markup
        if (isset($telegram['reply_markup'])) {
            $telegram['reply_markup'] = json_encode($telegram['reply_markup']);
        }

        // List and Carousel will be converted to Image + InlineButton
        


        // Receipt will be converted to MarkdownText

        // Facebook Quick Replies will convert to InlineKeyboard
        giga_remote_post($this->resource . $action, $telegram);
    }

    /**
     * Send typing indicator
     */
    public function sendTyping()
    {
        $lead_id = Conversation::get('lead_id');
        
        $body = [
            'chat_id'   => $lead_id,
            'action'    => 'typing'
        ];

        giga_remote_post($this->resource . 'sendChatAction', $body);
    }

    /**
     * Method to get current user
     * 
     * @return Array format as Facebook
     */
    public function getUser($lead_id)
    {
        // Because the requested data contains the user so we don't need to make any request
        $raw = Conversation::get('request_raw');
        
        if (isset($raw['callback_query'])) {
            $raw = $raw['callback_query'];
            $user = $raw['from'];
        }
        else {
            $user = $raw['message']['from'];
        }

        return [
            'user_id'    => $lead_id,
            'first_name' => $user['first_name'],
            'last_name'  => $user['last_name'],
            'source'     => 'telegram:' . Conversation::get('page_id'),
            'locale'     => str_replace('-', '_', $user['language_code'])
        ];
    }

    public function getWebhookInfo()
    {
        return giga_remote_get($this->resource . 'getWebhookInfo');
    }

    // Todo: When getUserInfo, read data from Telegram instead of Facebook
}