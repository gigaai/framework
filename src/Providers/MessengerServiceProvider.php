<?php

namespace GigaAI\Providers;

use GigaAI\Core\Command;
use GigaAI\Core\Config;
use Illuminate\Support\ServiceProvider;

use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

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
            'type' => 'command',
            'callback' => function ($content) {
                if (isset($content['command'])) {
                    $command = $content['command'];
                    $args = $content['args'];
                    \GigaAI\Core\Command::run($command, $args);
                }
            }
        ];
    
        \GigaAI\Core\DynamicParser::support($command_parser);
        
        $this->app->singleton(\GigaAI\MessengerBot::class, function ($app) {
            // Create new bot instance with multipage enabled
            $bot = new \GigaAI\MessengerBot([
                'multipage' => true,
            ]);
            
            return $bot;
        });
    }
}
