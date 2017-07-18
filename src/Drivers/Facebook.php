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