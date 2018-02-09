<?php

namespace GigaAI\Providers;

use GigaAI\Core\Command;
use GigaAI\Core\Config;
use Illuminate\Support\ServiceProvider;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;
use GigaAI\MessengerBot;

/**
 * Register a Service Provider in Laravel application
 *
 * @package GigaAI\Providers
 */
class MessengerServiceProvider extends ServiceProvider
{
    /**
     * Bootstrap the application services.
     *
     * @return void
     */
    public function boot()
    {
    }

    /**
     * Register the application services.
     *
     * @return void
     */
    public function register()
    {
        $command_parser = [
            'type'     => 'command',
            'callback' => 'command_parser_callback'
        ];

        \GigaAI\Core\DynamicParser::support($command_parser);

        $this->app->singleton(MessengerBot::class, function ($app) {
            // Create new bot instance with multipage enabled
            return new MessengerBot;
        });
    }
}