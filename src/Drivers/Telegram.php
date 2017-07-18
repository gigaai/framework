<?php

namespace GigaAI\Drivers;

class Telegram
{
    private $resource = 'https://api.telegram.org/bot418818588:AAHUT_KvzAIOPRRRMT_Lo6ChblvbqU1i9zc/';

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
        $facebook = [
            'object' => 'page',
            'entry' => [
                [
                    'id' => rand(),
                    'time' => $telegram->message->date,
                    'messaging' => [
                        [
                            'sender' => [
                                'id' => $telegram->message->from->id,
                            ],
                            'recipient' => [
                                'id' => rand()
                            ],
                            'timestamp' => $telegram->message->date,
                            'message' => [
                                'text' => $telegram->message->text
                            ]
                        ]
                    ]
                ]
            ]
        ];

        return $facebook;
    }

    /**
     * Convert Facebook request back to Telegram request
     */
    public function sendMessage($body)
    {
        $telegram = [
            'chat_id' => $body['recipient']['id'],
            'text'    => $body['message']['text']
        ];

        giga_remote_post($this->resource . 'sendMessage', $telegram);
    }

    public function getWebhookInfo()
    {
        return giga_remote_get($this->resource . 'getWebhookInfo');
    }

    // Todo: When getUserInfo, read data from Telegram instead of Facebook
}