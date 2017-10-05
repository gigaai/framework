<?php

namespace GigaAI\Core;

use GigaAI\Shortcodes\Shortcode;

class Parser
{
    public static function parseShortcodes($response, $dictionary = [])
    {
        if (empty($dictionary) || ! is_array($dictionary)) {
            return $response;
        }

        foreach ($dictionary as $shortcode => $value) {
            unset($dictionary[$shortcode]);

            $dictionary["[$shortcode]"] = $value;
        }

        foreach (['text', 'attachment.text'] as $node) {
            if ( ! empty(array_get($response, $node))) {
                $text = array_get($response, $node);
                $text = strtr($text, $dictionary);
                array_set($response, $node, $text);

                if (is_string($text)) {
                    array_set($response, $node, Shortcode::process($text));
                } else {
                    $response = $text;
                }
            }
        }

        // Replace in Generic
        return $response;
    }
}