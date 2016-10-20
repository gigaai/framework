<?php

namespace GigaAI\Http;

use GigaAI\Core\Config;

class ThreadSettings
{
    public static function init()
    {
        $allowed_actions = [
            'updateGetStartedButton',
            'updateGreetingText',
            'updatePersistentMenu'
        ];

        $action = Request::getReceivedData('giga_action');

        if (in_array($action, $allowed_actions))
        {
            @call_user_func(['ThreadSettings', $action]);
        }
    }

    public static function updateGetStartedButton()
    {
        $payload = Config::get('get_started_button_payload');

        $end_point = Request::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Request::$token;

        $params = array(
            'setting_type' => 'call_to_actions',
            'thread_state' => 'new_thread'
        );

        if ( ! empty($payload))
        {
            $params['call_to_actions'] = array(
                compact('payload')
            );

            $data = Request::send($end_point, $params);

            dd($data);
        }

        $data = Request::send($end_point, $params, 'delete');

        dd($data);
    }

    public static function updateGreetingText()
    {
        $greeting_text = Config::get('greeting_text');

        $end_point = Request::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Request::$token;

        $params = array(
            'setting_type' => 'greeting',
            'greeting' => array(
                'text' => $greeting_text
            )
        );

        $data = Request::send($end_point, $params);

        dd($data);
    }

    public static function updatePersistentMenu()
    {
        $menu = Config::get('persistent_menu');

        $end_point = Request::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Request::$token;

        $params = array(
            'setting_type' => 'call_to_actions',
            'thread_state' => 'existing_thread'
        );

        if ( ! empty($menu))
        {
            $params['call_to_actions'] = $menu;

            $data = Request::send($end_point, $params);

            dd($data);
        }

        $data = Request::send($end_point, $params, 'delete');

        dd($data);
    }
}