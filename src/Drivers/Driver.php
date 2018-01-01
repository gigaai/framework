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
        // Facebook driver by default
        $this->driver = new Facebook;

        // Loop through all available drivers
        foreach ($this->drivers as $driver_class) {
            // If driver format matches understand the request, then create new instance
            if ((new $driver_class)->expectedFormat($request)) {
                $this->driver = new $driver_class;
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
    public function run(&$request)
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

    public function sendMessages($batch)
    {
        $request = $this->driver->sendMessages($batch);
    }

    public function sendSubscribeRequest($attributes)
    {
        return $this->driver->sendSubscribeRequest($attributes);
    }
}