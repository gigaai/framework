<?php

namespace GigaAI\Shortcodes;

use GigaAI\Storage\Storage;
use Thunder\Shortcode\Event\FilterShortcodesEvent;
use Thunder\Shortcode\EventContainer\EventContainer;
use Thunder\Shortcode\Events;
use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Shortcode
{
    private static $shortcodes = [
        RandomText::class,
        Lead::class,
        PostGeneric::class,
    ];

    public static function process($content)
    {
        $handlers = new HandlerContainer();

        foreach (self::$shortcodes as $shortcode) {

            $reflection = new \ReflectionClass($shortcode);

            $shortcode_name = camel_to_snake($reflection->getShortName());

            $handlers->add($shortcode_name, function (ShortcodeInterface $s) use ($shortcode) {
                $newShortcode             = new $shortcode;
                $newShortcode->attributes = array_merge($newShortcode->attributes, $s->getParameters());
                $newShortcode->content    = $s->getContent();

                return $newShortcode->output();
            });
        }

        $events = new EventContainer();

        $events->addListener(Events::FILTER_SHORTCODES, function (FilterShortcodesEvent $event) use ($handlers) {
            $shortcodes = $event->getShortcodes();
            foreach ($shortcodes as $shortcode) {
                $handlers->add($shortcode->getName(), function (ShortcodeInterface $s) {
                    $shortcode_name = str_snake($s->getName());
                    $params = $s->getParameters();
                    $content = $s->getContent();
                    if (function_exists("giga_shortcode_{$shortcode_name}")) {
                        return call_user_func_array("giga_shortcode_{$shortcode_name}", [$params, $content]);
                    }

                    return Storage::get(null, $shortcode_name);
                });
            }
        });

        $processor = new Processor(new RegularParser(), $handlers);
        $processor = $processor->withEventContainer($events);

        $content = $processor->process($content);

        $output = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $output;
        }

        return $content;
    }
}