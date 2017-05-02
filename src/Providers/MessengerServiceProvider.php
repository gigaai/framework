<?php

namespace GigaAI\Providers;

use GigaAI\Core\Command;
use GigaAI\Core\Config;
use Illuminate\Support\ServiceProvider;

use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

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
        $handlers = new HandlerContainer();
    
        $handlers->add('random-text', function (ShortcodeInterface $s) {
            $content = $s->getContent();
            $rows = preg_split('/\r\n|[\r\n]/', $content);
        
            foreach ($rows as $n => $row) {
            
                if (empty($row)) {
                    unset($rows[$n]);
                    continue;
                }
            
                preg_match_all('/\((.*?)\)/', $row, $matches);
            
                if ( ! empty($matches[1])) {
                    foreach ($matches[1] as $index => $patterns) {
                        $patterns = explode('|', $patterns);
                    
                        $pick = $patterns[array_rand($patterns)];
                    
                        $row = str_replace($matches[0][$index], $pick, $row);
                    }
                }
            
                $rows[$n] = $row;
            }
        
            // Pick a random string from source
            return $rows[array_rand($rows)];
        });
    
        $processor = new Processor(new RegularParser(), $handlers);
    
        $shortcode_parser = [
            'type'     => 'shortcode',
            'callback' => function ($content) use ($processor) {
                $content = $processor->process($content);
            
                $output = json_decode($content, true);
            
                if (json_last_error() === JSON_ERROR_NONE) {
                    return $output;
                }
            
                return $content;
            },
        ];
    
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
    
        \GigaAI\Core\DynamicParser::support($shortcode_parser);
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
