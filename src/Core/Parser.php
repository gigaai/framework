<?php

namespace GigaAI\Core;

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
        if ( ! empty($response['text']))
            $response['text'] = strtr($response['text'], $dictionary);

        // Replace in Button
        if (! empty($response['attachment']['text']))
            $response['attachment']['text'] = strtr($response['text'], $dictionary);

        // Replace in Generic
        return $response;
    }
}