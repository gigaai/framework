<?php

namespace GigaAI\Drivers;

use GigaAI\Http\Request;
use GigaAI\Core\Config;
use GigaAI\Conversation\Conversation;

/**
 * Facebook Driver
 */
class Facebook implements DriverInterface
{
    public function __construct()
    {
        $this->verifyToken();
    }

    /**
     * Verify token from Facebook
     *
     * @return void
     */
    private function verifyToken()
    {
        $received = Request::getReceivedData();

        if (is_array($received) && isset($received['hub_verify_token'])
            && strtolower($received['hub_verify_token']) == 'gigaai'
        ) {
            echo $received['hub_challenge'];

            exit;
        }
    }

    /**
     * Get Facebook REST resouce
     *
     * @return String Resource URL
     */
    private function getResource()
    {
        return 'https://graph.facebook.com/v2.11/';
    }

    /**
     * Get Access Token
     *
     * @return String FB Page Access Token
     */
    private function getAccessToken()
    {
        return Config::get('access_token');
    }

    /**
     * Expected current message is belongs to FB or not. This helps Driver detect it.
     *
     * @param Array $request
     *
     * @return bool
     */
    public function expectedFormat($request)
    {
        return isset($request['object']);
    }

    /**
     * Format incoming request to FB format. Because the incoming request is already has FB format so we don't need to do anything here.
     *
     * @param array $request
     *
     * @return array $request
     */
    public function formatIncomingRequest($request)
    {
        return $request;
    }

    /**
     * Send the message back to FB
     *
     * @param Array $body Facebook Message
     *
     * @return Json
     */
    public function sendMessage($body)
    {
        return giga_remote_post($this->getResource() . 'me/messages?access_token=' . $this->getAccessToken(), $body);
    }

    public function sendMessages($messages)
    {
        $batch = [];

        foreach ($messages as $index => $message) {
            $previousAction = '';

            if ($index > 0) {
                $previousAction = '?order={result=index' . ($index - 1) . ':$.code}';
            }

            $batch[] = [
                'method'       => 'POST',
                'name'         => 'index' . $index,
                'relative_url' => 'me/messages' . $previousAction,
                'body'         => http_build_query($message)
            ];
        }

        $batch = json_encode($batch);

        return giga_remote_post(
            $this->getResource() . '?access_token=' . $this->getAccessToken(),
            compact('batch')
        );
    }

    /**
     * Send Typing Indicator
     *
     * @return Json
     */
    public function sendTyping()
    {
        $lead_id = Conversation::get('lead_id');

        $body = [
            'recipient'     => [
                'id' => $lead_id,
            ],
            'sender_action' => 'typing_on',
        ];

        return giga_remote_post($this->getResource() . 'me/messages?access_token=' . $this->getAccessToken(), $body);
    }

    /**
     * Get user data
     *
     * @param String $lead_id
     *
     * @return Array
     */
    public function getUser($lead_id)
    {
        $end_point = $this->getResource() . "{$lead_id}?access_token=" . $this->getAccessToken();

        $data = giga_remote_get($end_point);

        return json_decode($data, true);
    }

    /**
     * Send subscribe request to FB
     *
     * @return json response
     */
    public function sendSubscribeRequest($attributes)
    {
        $token = isset($attributes['access_token']) ? $attributes['access_token'] : $this->getAccessToken();

        return giga_remote_post($this->getResource() . 'me/subscribed_apps?access_token=' . $token);
    }
}
