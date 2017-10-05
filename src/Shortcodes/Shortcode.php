<?php

namespace GigaAI\Shortcodes;

use Thunder\Shortcode\HandlerContainer\HandlerContainer;
use Thunder\Shortcode\Parser\RegularParser;
use Thunder\Shortcode\Processor\Processor;
use Thunder\Shortcode\Shortcode\ShortcodeInterface;

class Shortcode
{
    private static $shortcodes = [
        RandomText::class,
        Lead::class,
    ];

    private static function loadHandlers()
    {
        $handlers = new HandlerContainer();

        foreach (self::$shortcodes as $shortcode) {

            $reflection = new \ReflectionClass($shortcode);

            $shortcode_name = camel_to_snake($reflection->getShortName());

            $handlers->add($shortcode_name, function (ShortcodeInterface $s) use ($shortcode) {
                $newShortcode             = new $shortcode;
                $newShortcode->attributes = $s->getParameters();
                $newShortcode->content    = $s->getContent();

                return $newShortcode->output();
            });
        }

        return $handlers;
    }

    public static function process($content)
    {
        $processor = new Processor(new RegularParser(), self::loadHandlers());

        $content = $processor->process($content);

        $output = json_decode($content, true);

        if (json_last_error() === JSON_ERROR_NONE) {
            return $output;
        }

        return $content;
    }
}