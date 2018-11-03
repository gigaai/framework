<?php

namespace GigaAI\Shortcodes;

use GigaAI\Conversation\Conversation;
use GigaAI\Storage\Storage;
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
        'nlp'          => Nlp::class
    ];

    /**
     * Process the shortcode.
     * Similar to do_shortcode()
     *
     * @param $content
     *
     * @return array|mixed|object|string
     */
    public static function process($content)
    {
        $content = self::parseVariables($content);

        $handlers = new HandlerContainer();

        foreach (self::$shortcodes as $shortcode_name => $class) {
            $handlers->add($shortcode_name, function (ShortcodeInterface $s) use ($class) {
                $shortcode             = new $class;
                $shortcode->attributes = array_merge($shortcode->attributes, $s->getParameters());
                $shortcode->content    = $s->getContent();

                return $shortcode->output();
            });
        }

        $handlers->setDefault(function (ShortcodeInterface $s) {
            $shortcode_name = snake_case($s->getName());

            $params  = $s->getParameters();
            $content = $s->getContent();

            if (function_exists("giga_shortcode_{$shortcode_name}")) {
                return call_user_func_array("giga_shortcode_{$shortcode_name}", [$params, $content]);
            }

            $lead = Conversation::get('lead');

            return $lead->data($shortcode_name);
        });

        $processor = new Processor(new RegularParser(), $handlers);

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
        if ( ! is_array($answer)) {
            return;
        }

        foreach ($answer as $key => $value) {
            $parsed = is_array($value) ? self::parse($value) : self::process($value);

            if ( ! empty($parsed)) {
                $answer[$key] = $parsed;
            } else {
                unset($answer[$key]);
            }
        }

        return $answer;
    }

    public static function parseVariables($content)
    {
        // Replace user input variable
        $content = str_replace('$input', Conversation::get('received_input'), $content);

        // Replace NLP variables
        preg_match_all("/(#\w+)/", $content, $variables);

        if (is_array($variables[0]) && ! empty($variables[0])) {
            $variables = $variables[0];

            foreach ($variables as $variable) {
                $variable = ltrim($variable, '#');
                $value    = Conversation::get('nlp')->filter($variable)->value();

                if ($value !== null) {
                    $content = str_replace('#' . $variable, $value, $content);
                }
            }
        }

        return $content;
    }
}
