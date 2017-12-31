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
    private function load()
    {
        // Get the received data from request
        $stream         = json_decode(file_get_contents('php://input'), true);
        self::$received = ( ! empty ($stream)) ? $stream : $_REQUEST;

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

    /**
     * Send a single message
     *
     * @param $message
     * @param $lead_id
     *
     * @return mixed
     */
    private function sendMessage($message, $lead_id = null)
    {
        $content = Shortcode::parse($message['content']);

        // Text as Raw Message
        if (isset($content['text']) && is_array($content['text'])) {
            $model   = new Model;
            $raw     = $model->parseWithoutSave($content['text']);
            $content = $raw[0];
        }

        // Text as Typing Indicator
        if (isset($content['text']) && is_string($content['text'])) {

            $is_typing = substr($content['text'], 0, 3);

            if ($is_typing === '...') {
                $delay = (float)ltrim($content['text'], ' .');

                $delay = $delay == 0 ? 3 : $delay;

                $this->driver->sendTyping();

                sleep($delay);

                return true;
            }
        }

        if (is_null($lead_id)) {
            $lead_id = Conversation::get('lead_id');
        }

        $content['metadata'] = 'SENT_BY_GIGA_AI';

        $body = [
            'recipient' => [
                'id' => $lead_id,
            ],
            'message'   => $content,
        ];

        if ($message['type'] !== 'text') {
            sd($body);
        }

        return $this->driver->sendMessage($body);
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
     * @param mixed $lead_id
     */
    private function sendMessages($messages, $lead_id = null)
    {
        foreach ($messages as $message) {
            $this->sendMessage($message, $lead_id);
        }
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
            ! empty($event['message']['quick_reply']['payload'])) {
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