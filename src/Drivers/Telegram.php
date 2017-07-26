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
class Telegram implements DriverInterface
{    
    private function getResource($append = '')
    {
        $token = Config::get('page_access_token');
        
        return "https://api.telegram.org/bot{$token}/{$append}";
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
            } else if (isset($telegram['message'])) {
                $sender_id = $telegram['message']['from']['id'];
                $time = $telegram['message']['date'];
            } else {
                return $telegram;
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

        return $telegram;
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

                    return $this->callMethod($action, $telegram);
                }
            }
            
            // Sending Button, Generic, List
            if ($message['attachment']['type'] === 'template' && 
                isset($message['attachment']['payload'])):

                // Facebook Button will be converted to InlineKeyboard
                if ($message['attachment']['payload']['template_type'] === 'button') {
                    return $this->sendButtons($message['attachment']['payload']);
                }

                // Facebook Generic will be converted to Image + Text + Button
                if ($message['attachment']['payload']['template_type'] === 'generic') {
                    return $this->sendGeneric($message['attachment']['payload']);
                }
            endif;
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

        // Facebook Quick Replies will convert to InlineKeyboard
        return giga_remote_post($this->getResource($action), $telegram);
    }

    /**
     * Call Telegram method via HTTP
     *
     * @see https://core.telegram.org/bots/api#available-methods
     */
    private function callMethod($method, $params)
    {
        $params['chat_id'] = Conversation::get('lead_id');

        return giga_remote_post($this->getResource($method), $params);
    }

    private function sendButtons($payload)
    {
        $buttons = $this->convertToInlineKeyboard($payload['buttons']);
        $buttons['text'] = $payload['text'];

        return $this->callMethod('sendMessage', $buttons);
    }

    private function convertToInlineKeyboard($buttons)
    {
        $keyboard = [
            'reply_markup' => [
                'inline_keyboard' => []
            ]
        ];
        
        foreach ($buttons as $button) :
            $telegram_button = [
                'text' => $button['title']
            ];

            if (isset($button['type']) && $button['type'] === 'web_url') {
                $telegram_button['url'] = $button['url'];
            }

            if (isset($button['type']) && $button['type'] === 'postback') {
                $telegram_button['callback_data'] = $button['payload'];
            }

            $keyboard['reply_markup']['inline_keyboard'][] = [
                $telegram_button
            ];
        endforeach;

        $keyboard['reply_markup'] = json_encode($keyboard['reply_markup']);

        return $keyboard;
    }

    /**
     * Convert Generic to Photo with Caption + Buttons
     *
     * @param Array $payload
     * @return Json
     */
    private function sendGeneric($payload)
    {
        foreach ($payload['elements'] as $element) {
            $generic = [];

            if (isset($element['image_url'])) {
                $generic['photo']   = $element['image_url'];
                $generic['caption'] = $element['title'];
                $keyboard           = $this->convertToInlineKeyboard($element['buttons']);
                $generic['reply_markup'] = $keyboard['reply_markup'];
                
                $this->callMethod('sendPhoto', $generic);
            } else {
                $this->sendButtons([
                    'text'      => $element['title'],
                    'buttons'   => $generic['buttons']
                ]);
            }
        }
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

        giga_remote_post($this->getResource('sendChatAction'), $body);
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
}