<?php

namespace GigaAI\Drivers;

class Facebook implements DriverInterface
{
    public $token = null;

    public $resource = null;

    public function __construct()
    {
        $token          = get_access_token();

        $this->resource = "https://graph.facebook.com/v2.6/{$token}";
    }

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
        return giga_remote_post($this->resource . 'me/messages?access_token=' . $this->token, $body);
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
}