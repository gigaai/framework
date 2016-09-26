<?php


namespace GigaAI\Core;


use GigaAI\Http\HttpClient;
use GigaAI\Messages\AbstractMessage;

/**
 * Class MessageSender
 *
 * @package GigaAI\Core
 */
class MessageSender
{
    /**
     * Base URL for FB Messenger platform
     *
     * @see https://developers.facebook.com/docs/messenger-platform/send-api-reference
     */
    const FB_MESSENGER_PLATFORM_URL = 'https://graph.facebook.com/v2.6/me/';

    /**
     * @var HttpClient
     */
    private $httpClient;

    private $accessToken;

    /**
     * MessageSender constructor.
     *
     * @param HttpClient $httpClient
     * @param $accessToken
     */
    public function __construct(HttpClient $httpClient, $accessToken)
    {
        $this->httpClient = $httpClient;
        $this->accessToken = $accessToken;
    }

    /**
     * Send the message to FB Messenger Platform
     *
     * @param AbstractMessage $message
     *
     * @return mixed
     */
    public function send(AbstractMessage $message)
    {
        $url = self::FB_MESSENGER_PLATFORM_URL . 'messages?access_token=' . $this->accessToken;

        return $this->httpClient->post($url, $message->getRawMessage());
    }
}