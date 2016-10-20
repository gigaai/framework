<?php

namespace GigaAI\Http;

use GigaAI\Core\Config;

/**
 * Class WebService
 *
 * @package Model
 */
class Request
{
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
     * Setup data and run command based on received data
     */
    public function __construct()
    {
        self::$received = (!empty ($_REQUEST)) ? $_REQUEST : json_decode(file_get_contents('php://input'));

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
    public function getReceivedData($key = null)
    {
        $received = self::$received;

        if ($key !== null) {
            if (isset($received[$key]))
                return $received[$key];

            if (isset($received->$key))
                return $received->$key;

            return '';
        }

        return $received;
    }

    private function send($end_point, $body = [], $method = 'post')
    {
        return call_user_func_array('giga_remote_' . $method, [$end_point, $body]);
    }

    public function getUserProfile($user_id)
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
    public function verifyTokenFromFacebook()
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

		if (is_array($this->getReceivedData()) && array_key_exists('subscribe', $this->getReceivedData())) {

			$post = $this->send($end_point);

			dd($post);
		}
	}

	/**
	 * Magic method to load request
	 *
	 * @param $name
	 * @param array $args
	 * @return $this
	 */
	public function __call($name, $args = array())
	{
		return call_user_func_array(array($this, $name), $args);
	}

	/**
	 * Magic method to load storage driver methods
	 *
	 * @param $name
	 * @param array $args
	 * @return $this
	 */
	public static function __callStatic($name, $args = array())
	{
		$storage = new self;

		return $storage->__call($name, $args);
	}
}