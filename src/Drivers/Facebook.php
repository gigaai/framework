<?php

namespace GigaAI\Drivers;

class Facebook
{
    public $resource = '';
    public $token = '';

    public function expectedFormat($request)
    {
        return isset($request->object);
    }

    public function formatIncomingRequest($request)
    {
        return $request;
    }

    public function sendMessage($body)
    {
        giga_remote_post($this->resource . 'me/messages?access_token=' . $this->token, $body);
    }

    public function sendTyping()
    {
        $lead_id = Conversation::get('lead_id');

        $body = [
            'recipient' => [
                'id' => $lead_id
            ],
            'sender_action' => 'typing_on'
        ];

        giga_remote_post($this->resource . "me/messages?access_token=" . self::$token, $body);
    }

    public function getUser($lead_id)
    {
        $end_point  = $this->resource . "{$user_id}?access_token=" . self::$token;
        
        $data       = giga_remote_get($end_point);
        
        return json_decode($data, true);
    }

    /**
     * Get Webhook Info
     *
     * @return void
     */
    public function getWebhookInfo()
    {
        //
    }
}