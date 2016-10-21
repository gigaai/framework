<?php

namespace GigaAI\Http;

use GigaAI\Core\Config;
use GigaAI\Core\Parser;
use GigaAI\Storage\Storage;
use GigaAI\Shared\EasyCall;
/**
 * Class Request
 *
 * @package Model
 */
class Request
{
    use EasyCall;

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
    const PLATFORM_ENDPOINT = 'https://graph.facebook.com/v2.6/';

    /**
     * Page access token
     *
     * @var string
     */
    public static $token;

    /**
     * This class is singleton
     *
     * @var Request
     */
    private static $instance;

    /**
     * Setup data and run command based on received data
     */
    private function load()
    {
        self::$received = (!empty ($_REQUEST)) ? $_REQUEST : json_decode(file_get_contents('php://input'));

        // Sleep if app don't get any request
        if (empty(self::$received))
            return;

        self::$token = Config::get('page_access_token');

        $this->verifyTokenFromFacebook();

		$this->subscribeFacebook();

        // Run thread settings
        ThreadSettings::init();
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

        return $received;
    }

    private function send($end_point, $body = [], $method = 'post')
    {
        return call_user_func_array('giga_remote_' . $method, [$end_point, $body]);
    }

    private function getUserProfile($user_id)
    {
        $end_point  = self::PLATFORM_ENDPOINT . "{$user_id}?access_token=" . self::$token;

        $data       = file_get_contents($end_point);

        return json_decode($data, true);
    }

    /**
     * Verify token from Facebook
     *
     * @return void
     */
    private function verifyTokenFromFacebook()
    {
        $received = $this->getReceivedData();

        if (is_array($received) && isset($received['hub_verify_token']) && $received['hub_verify_token'] == 'GigaAI'
        ) {

            echo $received['hub_challenge'];

            exit;
        }
    }

    private function subscribeFacebook()
	{
        $end_point = self::PLATFORM_ENDPOINT . "me/subscribed_apps?access_token=" . self::$token;

        $received = $this->getReceivedData('subscribe');

		if ($received != null) {

			$post = $this->send($end_point);

			dd($post);
		}
	}

    /**
     * Send a single message
     *
     * @param $message
     * @param $lead_id
     */
	private function sendMessage($message, $lead_id)
    {
        $message = Parser::parseShortcodes($message, Storage::get($lead_id));

        $response['metadata'] = 'SENT_BY_GIGA_AI';

        $body = [
            'recipient' => [
                'id' => $lead_id
            ],
            'message' => $message
        ];

        Request::send(self::PLATFORM_ENDPOINT . "me/messages?access_token=" . self::$token, $body);
    }

    private function sendMessages($messages, $lead_id)
    {
        array_map(['Request', 'sendMessage'], $messages);
    }

    public static function getInstance()
    {
        if (null === self::$instance) {
            self::$instance = new static();
            self::$instance->load();
        }

        return self::$instance;
    }

    private function __construct(){}
    private function __clone(){}
    private function __wakeup(){}
}