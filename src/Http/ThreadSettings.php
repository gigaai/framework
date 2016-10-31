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

        $request    = Request::getInstance();
        $action     = $request->getReceivedData('giga_action');
        if ($action != null && in_array($action, $allowed_actions))
        {
            @call_user_func([__CLASS__, $action]);
        }
    }

    public static function updateGetStartedButton()
    {
        $payload = Config::get('get_started_button_payload');

        $end_point = Request::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Request::$token;

        $params = [
            'setting_type' => 'call_to_actions',
            'thread_state' => 'new_thread'
        ];

        if ( ! empty($payload))
        {
            $params['call_to_actions'] = [
                compact('payload')
            ];

            return Request::send($end_point, $params);
        }

        Request::send($end_point, $params, 'delete');
    }

    public static function updateGreetingText()
    {
        $greeting_text = Config::get('greeting_text');

        $end_point = Request::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Request::$token;

        $params = [
            'setting_type' => 'greeting',
            'greeting' => [
                'text' => $greeting_text
            ]
        ];

        return Request::send($end_point, $params);
    }

    public static function updatePersistentMenu()
    {
        $menu = Config::get('persistent_menu');

        $end_point = Request::PLATFORM_ENDPOINT . 'me/thread_settings?access_token=' . Request::$token;

        $params = [
            'setting_type' => 'call_to_actions',
            'thread_state' => 'existing_thread'
        ];

        if ( ! empty($menu))
        {
            $params['call_to_actions'] = $menu;

            return Request::send($end_point, $params);
        }

        Request::send($end_point, $params, 'delete');
    }
}