<?php

namespace GigaAI\Http;

use GigaAI\Conversation\Conversation;
use GigaAI\Core\Config;
use GigaAI\Core\Logger;
use GigaAI\Core\Model;
use GigaAI\Core\Parser;
use GigaAI\Shared\Singleton;
use GigaAI\Shortcodes\Shortcode;
use GigaAI\Storage\Storage;
use GigaAI\Shared\EasyCall;
use GigaAI\Drivers\Driver;
use GigaAI\Core\DynamicParser;
use SuperClosure\Serializer;
use GigaAI\Storage\Eloquent\Lead;

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
     * Page access token
     *
     * @var string
     */
    public static $token;

    /**
     * Setup data and run command based on received data
     */
    private function load($simulate = [])
    {
        // Get the received data from request
        $stream         = json_decode(file_get_contents('php://input'), true);
        self::$received = (!empty($stream)) ? $stream : $_REQUEST;

        if (!empty($simulate)) {
            self::$received = $simulate;
        }

        Conversation::set('request_raw', self::$received);

        // Load driver to parse request
        $this->driver = Driver::getInstance();
        $this->driver->run(self::$received);

        Logger::put($stream, 'incoming');

        self::$token = Config::get('access_token', self::$token);

        $this->subscribe();
    }

    /**
     * Get received request
     *
     * @param null $key
     *
     * @return mixed
     */
    private function getReceivedData($key = null)
    {
        $received = self::$received;

        if ($key !== null) {
            if (is_array($received) && isset($received[$key])) {
                return $received[$key];
            }

            if (isset($received->$key)) {
                return $received->$key;
            }

            return null;
        }

        return is_object($received) ? (array)$received : $received;
    }

    /**
     * Send request
     *
     * @param String $end_point
     * @param array  $body
     * @param string $method
     *
     * @return mixed
     */
    private function send($end_point, $body = [], $method = 'post')
    {
        return call_user_func_array('giga_remote_' . $method, [$end_point, $body]);
    }

    /**
     * Get User Profile
     *
     * @param String $user_id
     *
     * @return String Json
     */
    private function getUserProfile($user_id)
    {
        return $this->driver->getUser($user_id);
    }

    /**
     * Subscribe Facebook
     *
     * @return void
     */
    private function subscribe($attributes = [])
    {
        $received = $this->getReceivedData('subscribe');

        if ($received != null) {
            return $this->driver->sendSubscribeRequest($attributes);
        }
    }

    private function sendSubscribeRequest($attributes = [])
    {
        return $this->driver->sendSubscribeRequest($attributes);
    }

    private function prepareMessage($message, $attributes = [], $lead = null)
    {
        $model   = new Model;

        if ($message['type'] === 'callback' || $message['type'] === 'command') {
            $serializer = new Serializer;

            if (is_string($message['content']) && giga_match('%SerializableClosure%', $message['content'])) {
                $message['content'] = $serializer->unserialize($message['content']);
            }

            $return = DynamicParser::parse($message);
            
            // If the callback return, we'll send that message to user.
            if ($return != null || !empty($return)) {
                $answers = $model->parse($return);
                
                $this->sendMessages($answers);
            }

            return null;
        }
        
        $content = Shortcode::parse($message['content']);
        
        if (empty($content)) {
            return null;
        }

         // Text as Typing Indicator
        if (isset($content['text']) && is_string($content['text'])) {
            $is_typing = substr($content['text'], 0, 3);

            if ($is_typing === '...') {
                $delay = (float)ltrim($content['text'], ' .');

                $delay = $delay == 0 ? 3 : $delay;

                $this->driver->sendTyping();

                sleep($delay);

                return null;
            }
        }

        if ($message['type'] === 'typing') {
            $delay = isset($content['typing']) && is_numeric($content['typing']) ? $content['typing'] : 3;

            $this->driver->sendTyping();

            sleep($delay);

            return null;
        }

        // Text as Raw Message
        if (isset($content['text']) && is_array($content['text'])) {
            $raw     = $model->parseWithoutSave($content['text']);
            $content = $raw[0];
        }
        
        if (is_null($lead)) {
            $lead = Conversation::get('lead');
        }

        $content['metadata'] = 'SENT_BY_GIGA_AI';

        $response = [
            'messaging_type' => isset($attributes['messaging_type']) ? $attributes['messaging_type'] : 'RESPONSE',
            'recipient' => [
                'id' => $lead->user_id,
            ],
            'message'   => $content,
        ];

        $response['tag'] = isset($attributes['tag']) ? $attributes['tag'] : '';

        return $response;
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
     * @param       $messages
     * @param mixed $lead
     */
    private function sendMessages($messages, $attributes = [], $lead = null)
    {
        $accessToken = Config::get('access_token');

        if ($accessToken === null && $lead !== null) {
            $instance = $lead->instance()->first();
            
            if ($instance !== null) {
                Config::set($instance->meta);
            }
        }

        $batch = [];
        
        foreach ($messages as $message) {
            $message = $this->prepareMessage($message, $attributes, $lead);
            
            if ( ! empty($message)) {
                $batch[] = $message;
            }
        }
        
        $batch = array_values(array_filter($batch));
        
        $this->driver->sendMessages($batch);
    }

    /**
     * Get Message Type and Pattern of an Event
     *
     * @param $event
     *
     * @return array
     */
    private function getTypeAndPattern($event)
    {
        $type    = 'text';
        $pattern = '';

        // For Text Message
        if (isset($event['message']) && isset($event['message']['text'])) {
            $pattern = $event['message']['text'];
        }

        // For Attachment Message
        if (isset($event['message']) && isset($event['message']['attachments'])) {
            $type = 'attachment';

            if (isset($event['message']['attachments'][0]['type'])) {
                $pattern = $event['message']['attachments'][0]['type'];
            }
        }

        // For Payload Message
        if (isset($event['postback']['payload'])) {
            $type    = 'payload';
            $pattern = $event['postback']['payload'];
        }

        // For Quick Replies
        if (isset($event['message']) && isset($event['message']['quick_reply']) &&
            !empty($event['message']['quick_reply']['payload'])) {
            $type    = 'payload';
            $pattern = $event['message']['quick_reply']['payload'];
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
