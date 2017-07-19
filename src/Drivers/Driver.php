<?php

namespace GigaAI\Drivers;

use GigaAI\Shared\Singleton;
use GigaAI\Shared\EasyCall;

class Driver
{
    use EasyCall, Singleton;

    /**
     * Available Drivers
     *
     * @var array
     */
    public $drivers = [
        Facebook::class,
        Telegram::class,
        // MicrosoftBotFramework::class
    ];

    /**
     * Default Messenger Bot Driver
     *
     * @var Class
     */
    public $driver = null;

    /**
     * Detect driver based on incoming request
     *
     * @param Array $request
     * @return void
     */
    public function detect($request)
    {
        $this->driver = new Telegram;
        // Loop through all available drivers
        foreach ($this->drivers as $driver) {

            // If driver format matches understand the request, then create new instance
            if ((new $driver)->expectedFormat($request)) {
                $this->driver = new $driver;
                break;
            }
        }
    }

    public function getDriver()
    {
        return $this->driver;
    }

    /**
     * Detect the driver and format the request to Facebook format
     *
     * @param Mixed $request
     * @return void
     */
    public function detectAndFormat(&$request)
    {
        $this->detect($request);
        $request = $this->driver->formatIncomingRequest($request);
    }

    public function getUser($lead_id)
    {
        return $this->driver->getUser($lead_id);
    }

    public function sendTyping()
    {
        $this->driver->sendTyping();
    }
    /**
     * Send message with body
     * 
     * @param Array $body Body with Facebook format
     */
    public function sendMessage($body)
    {
        $request = $this->driver->sendMessage($body);
    }

    /**
     * Get Webhook Info
     * 
     * @return array
     */
    public function getWebhookInfo()
    {
        return $this->driver->getWebhookInfo();
    }
}