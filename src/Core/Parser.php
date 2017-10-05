<?php

namespace GigaAI\Core;

use GigaAI\Shortcodes\Shortcode;

class Parser
{
    public static function parseShortcodes($response, $dictionary = [])
    {
        if (empty($dictionary) || ! is_array($dictionary))
            return $response;

        foreach ($dictionary as $shortcode => $value)
        {
            unset($dictionary[$shortcode]);

            $dictionary["[$shortcode]"] = $value;
        }

        // Replace in Text
        if ( ! empty($response['text'])) {

            // Parse legacy shortcodes
            $response['text'] = strtr($response['text'], $dictionary);

            // Parse shortcode with new library
            $response['text'] = Shortcode::process($response['text']);
        }

        // Replace in Button
        if (! empty($response['attachment']['text'])) {
            $response['attachment']['text'] = strtr($response['text'], $dictionary);

            $response['attachment']['text'] = Shortcode::process($response['text'], $dictionary);
        }
        // Replace in Generic
        return $response;
    }
}