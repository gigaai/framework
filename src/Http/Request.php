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

    public function __construct()
    {
        $this->received = (!empty ($_REQUEST)) ? $_REQUEST : json_decode(file_get_contents('php://input'));
    }

    public function getReceivedData()
    {
        return $this->received;
    }

    public function send($end_point, $body = array(), $method = 'post')
    {
        $request = array(
            'timeout' => 120,
            'redirection' => 5,
            'httpversion' => '1.0',
            'blocking' => true,
            'headers'     => array(
                'Content-Type' => 'application/json'
            )
        );

        if ( ! empty($body))
            $request['body'] = $body;

        call_user_func_array('giga_remote_' . $method, array($end_point, $request));
    }

    public function getUserProfile($user_id)
    {
        $end_point  = "https://graph.facebook.com/v2.6/{$user_id}?access_token=" . Config::get('page_access_token');

        $data       = file_get_contents($end_point);

        return json_decode($data, true);
    }

    public function updateWelcome( $node )
    {
        $end_point = 'https://graph.facebook.com/v2.6/' . PAGE_ID . '/thread_settings?access_token=' . Config::get('page_access_token');

        $message = $this->normalizeNode( $node );

        $this->send($end_point, array(
            'setting_type' => 'call_to_actions',
            'thread_state' => 'new_thread',
            'call_to_actions' => array(
                array(
                    'message' => $message
                )
            )
        ));
    }
}