<?php

namespace GigaAI\Drivers;

use GigaAI\Conversation\Conversation;
use GigaAI\Core\Config;

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

    public function __construct()
    {
        $token = Config::get('messenger.telegram_token');

        $this->resource = "https://api.telegram.org/bot{$token}/";
    }
    /**
     * Expected format to be sent from Telegram
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
     * Convert Telegram request to Facebook request
     */
    public function formatIncomingRequest($telegram)
    {
        if ( ! empty($telegram) && is_array($telegram)) {
            $facebook = [
                'object' => 'page',
                'entry' => [
                    [
                        'id' => rand(),
                        'time' => $telegram['message']['date'],
                        'messaging' => [
                            [
                                'sender' => [
                                    'id' => $telegram['message']['from']['id'],
                                ],
                                'recipient' => [
                                    'id' => rand()
                                ],
                                'timestamp' => $telegram['message']['date'],
                                'message' => [
                                    'text' => $telegram['message']['text']
                                ]
                            ]
                        ]
                    ]
                ]
            ];

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
        $type = 'Message';
        
        $telegram = [
            'chat_id' => $body['recipient']['id']
        ];

        $message = $body['message'];

        // Send Text
        if (isset($message['text'])) {
            $telegram['text'] = $message['text'];
        }

        // Send Attachment
        if (isset($message['attachment']['type'])) {
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
                    $type = ucfirst($telegram_template);
                }
            }
        }


        giga_remote_post($this->resource . 'send' . $type, $telegram);
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
        $user = $raw['message']['from'];

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