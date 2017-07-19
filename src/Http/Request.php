<?php

namespace GigaAI\Http;

use GigaAI\Conversation\Conversation;
use GigaAI\Core\Config;
use GigaAI\Core\Logger;
use GigaAI\Core\Parser;
use GigaAI\Shared\Singleton;
use GigaAI\Storage\Storage;
use GigaAI\Shared\EasyCall;
use GigaAI\Drivers\Driver;

/**
 * Class Request
 *
 * @package Model
 */
class Request
{
    use Singleton, EasyCall;
    
    /**
     * Received request data
     *
     * @var mixed
     */
    public static $received;
    
    /**
     * Facebook Messenger Bot endpoint
     *
     * @var string
     */
    const PLATFORM_RESOURCE = 'https://graph.facebook.com/v2.6/';
    
    /**
     * Page access token
     *
     * @var string
     */
    public static $token;
    
    /**
     * Setup data and run command based on received data
     */
    private function load()
    {
        // Get the received data from request
        $stream = json_decode(file_get_contents('php://input'), true);
        self::$received = (!empty ($stream)) ? $stream : $_REQUEST;

        Conversation::set('request_raw', self::$received);

        // Load driver to parse request
        $this->driver = Driver::getInstance();
        $this->driver->detectAndFormat(self::$received);

        // Logger::put($stream, 'incoming');

        self::$token = Config::get('page_access_token', self::$token);
        
        $this->verifyTokenFromFacebook();
        
        $this->subscribeFacebook();
    }
    
    /**
     * Get received request
     *
     * @param null $key
     * @return mixed
     */
    private function getReceivedData($key = null)
    {
        $received = self::$received;
        
        if ($key !== null) {
            if (is_array($received) && isset($received[$key]))
                return $received[$key];
            
            if (isset($received->$key))
                return $received->$key;
            
            return null;
        }
        
        return is_object($received) ? (array) $received : $received;
    }
    
    /**
     * Send request
     *
     * @param String $end_point
     * @param array $body
     * @param string $method
     */
    private function send($end_point, $body = [], $method = 'post')
    {
        return call_user_func_array('giga_remote_' . $method, [$end_point, $body]);
    }
    
    /**
     * Get User Profile
     *
     * @param String $user_id
     * @return String Json
     */
    private function getUserProfile($user_id)
    {
        return $this->driver->getUser($user_id);
    }
    
    /**
     * Verify token from Facebook
     *
     * @return void
     */
    private function verifyTokenFromFacebook()
    {
        $received = $this->getReceivedData();
        
        if (is_array($received) && isset($received['hub_verify_token'])
            && strtolower($received['hub_verify_token']) == 'gigaai'
        ) {
            echo $received['hub_challenge'];
            
            exit;
        }
    }
    
    /**
     * Subscribe Facebook
     *
     * @return void
     */
    private function subscribeFacebook()
    {
        $received = $this->getReceivedData('subscribe');
        
        if ($received != null) {
            dd($this->sendSubscribeRequestToTelegram());
        }
    }
    
    private function sendSubscribeRequest()
    {
        $end_point = self::PLATFORM_RESOURCE . "me/subscribed_apps?access_token=" . self::$token;
        
        return $this->send($end_point);
    }

    private function sendSubscribeRequestToTelegram()
    {
        $end_point = 'https://api.telegram.org/bot418818588:AAHUT_KvzAIOPRRRMT_Lo6ChblvbqU1i9zc/setWebhook';
        
        return $this->send($end_point, [
            'url' => 'https://4fcc11c1.ngrok.io/api/messenger'
        ]);
    }
    
    /**
     * Send a single message
     *
     * @param $message
     * @param $lead_id
     */
    private function sendMessage($message, $lead_id = null)
    {
        if (is_null($lead_id)) {
            $lead_id = Conversation::get('lead_id');
        }
        
        $message                = Parser::parseShortcodes($message, Storage::get($lead_id));
        
        $message['metadata']    = 'SENT_BY_GIGA_AI';
        
        $body = [
            'recipient' => [
                'id' => $lead_id
            ],
            'message' => $message
        ];

        $this->driver->sendMessage($body);
        // Request::send(self::PLATFORM_RESOURCE . "me/messages?access_token=" . self::$token, $body);
    }

    /**
     * Send typing indicator to Facebook
     *
     * @return void
     */
    private function sendTyping()
    {
        $this->driver->sendTyping();
    }
    
    /**
     * Send multiple messages
     *
     * @param $messages
     * @param null $lead_id
     */
    private function sendMessages($messages, $lead_id = null)
    {
        foreach ($messages as $message)
        {
            $this->sendMessage($message, $lead_id);
        }
    }
    
    /**
     * Get Message Type and Pattern of an Event
     *
     * @param $event
     * @return array
     */
    private function getTypeAndPattern($event)
    {
        $type       = 'text';
        $pattern    = '';
        
        // For Text Message
        if (isset($event['message']) && isset($event['message']['text']))
            $pattern    = $event['message']['text'];
        
        // For Attachment Message
        if (isset($event['message']) && isset($event['message']['attachments'])) {
            $type = 'attachment';
            
            if (isset($event['message']['attachments'][0]['type']))
                $pattern = $event['message']['attachments'][0]['type'];
        }
        
        // For Payload Message
        if (isset($event['postback']['payload'])) {
            $type       = 'payload';
            $pattern    = $event['postback']['payload'];
        }
        
        // For Quick Replies
        if (isset($event['message']) && isset($event['message']['quick_reply']) &&
            ! empty($event['message']['quick_reply']['payload']))
        {
            $type       = 'payload';
            $pattern    = $event['message']['quick_reply']['payload'];
        }
        
        return compact('type', 'pattern');
    }
    
    
    /**
     * Override Singleton trait
     *
     * @return Request|static
     */
    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new static();
            self::$instance->load();
        }
        
        return self::$instance;
    }
}