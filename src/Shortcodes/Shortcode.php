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
    /**
     * Built in shortcodes
     *
     * @var array
     */
    private static $shortcodes = [
        'random-text'  => RandomText::class,
        'lead'         => Lead::class,
        'post-generic' => PostGeneric::class,
        'input'        => Input::class,
    ];

    /**
     * Process the shortcode.
     *
     * Similar to do_shortcode()
     *
     * @param $content
     *
     * @return array|mixed|object|string
     */
    public static function process($content)
    {
        $handlers = new HandlerContainer();

        foreach (self::$shortcodes as $shortcode_name => $class) {
            $handlers->add($shortcode_name, function (ShortcodeInterface $s) use ($class) {
                $shortcode             = new $class;
                $shortcode->attributes = array_merge($shortcode->attributes, $s->getParameters());
                $shortcode->content    = $s->getContent();

                return $shortcode->output();
            });
        }

        $events = new EventContainer();

        $events->addListener(Events::FILTER_SHORTCODES, function (FilterShortcodesEvent $event) use ($handlers) {
            $shortcodes = $event->getShortcodes();

            foreach ($shortcodes as $shortcode) {

                $shortcode_name = $shortcode->getName();

                if (array_key_exists($shortcode_name, self::$shortcodes)) {
                    continue;
                }

                $handlers->add($shortcode_name, function (ShortcodeInterface $s) use ($shortcode_name) {
                    $shortcode_name = str_snake($s->getName());
                    $params         = $s->getParameters();
                    $content        = $s->getContent();

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

    /**
     * Recursive parse shortcode from array
     *
     * @param $answer
     *
     * @return mixed
     */
    public static function parse($answer)
    {
        foreach ($answer as $key => $value) {
            $answer[$key] = is_string($value) ? self::process($value) : self::parse($value);
        }

        return $answer;
    }
}