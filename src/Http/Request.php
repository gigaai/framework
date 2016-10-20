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
    public $received;

    const PLATFORM_ENDPOINT = 'https://graph.facebook.com/v2.6/';

    public function __construct()
    {
        $this->received = (!empty ($_REQUEST)) ? $_REQUEST : json_decode(file_get_contents('php://input'));

        if (is_array($this->received) && ! empty($this->received['giga_action']) &&
            in_array(trim($this->received['giga_action']), array(
                'updateGetStartedButton',
                'updateGreetingText',
                'updatePersistentMenu'
            )))
        {
            $action = trim($this->received['giga_action']);

            @call_user_func(array($this, $action));
        }

        $this->verifyTokenFromFacebook();

		$this->subscribeFacebook();
    }

    public function getReceivedData()
    {
        return $this->received;
    }

    private function send($end_point, $body = array(), $method = 'post')
    {
        return call_user_func_array('giga_remote_' . $method, array($end_point, $body));
    }

    private function sendSubscribe()
    {
        $end_point = self::PLATFORM_ENDPOINT . "me/subscribed_apps?access_token=" . Config::get('page_access_token');

        return $this->send($end_point);
    }

    public function getUserProfile($user_id)
    {
        $end_point  = self::PLATFORM_ENDPOINT . "{$user_id}?access_token=" . Config::get('page_access_token');

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
        if (is_array($this->received) &&
            isset($this->received['hub_verify_token']) &&
            $this->received['hub_verify_token'] == 'GigaAI'
        ) {

            echo $this->received['hub_challenge'];

            exit;
        }
    }

    private function subscribeFacebook()
	{
		if (is_array($this->received) && array_key_exists('subscribe', $this->received)) {

			$post = $this->sendSubscribe();

			dd($post);
		}
	}

    public function updateGetStartedButton()
    {
        $payload = Config::get('get_started_button_payload');

        $end_point = self::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Config::get('page_access_token');

        $params = array(
            'setting_type' => 'call_to_actions',
            'thread_state' => 'new_thread'
        );

        if ( ! empty($payload))
        {
            $params['call_to_actions'] = array(
                compact('payload')
            );

            $data = $this->send($end_point, $params);

            dd($data);
        }

        $data = $this->send($end_point, $params, 'delete');

        dd($data);
    }

    public function updateGreetingText()
    {
        $greeting_text = Config::get('greeting_text');

        $end_point = self::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Config::get('page_access_token');

        $params = array(
            'setting_type' => 'greeting',
            'greeting' => array(
                'text' => $greeting_text
            )
        );

        $data = $this->send($end_point, $params);

        dd($data);
    }

    public function updatePersistentMenu()
    {
        $menu = Config::get('persistent_menu');

        $end_point = self::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Config::get('page_access_token');

        $params = array(
            'setting_type' => 'call_to_actions',
            'thread_state' => 'existing_thread'
        );

        if ( ! empty($menu))
        {
            $params['call_to_actions'] = $menu;

            $data = $this->send($end_point, $params);

            dd($data);
        }

        $data = $this->send($end_point, $params, 'delete');

        dd($data);
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