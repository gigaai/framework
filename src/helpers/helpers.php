<?php

function giga_remote_post($url, $args = array())
{
    return GigaAI\Http\Http::post($url, $args);
}

function giga_remote_get($url)
{
    return GigaAI\Http\Http::get($url);
}

function giga_remote_delete($url, $args = array())
{
    return GigaAI\Http\Http::delete($url, $args);
}

/**
 * Match user entered text with bot pattern
 *
 * @param  String $pattern Pattern
 * @param  String $string User Text
 *
 * @return bool
 */
function giga_match($pattern, $string)
{
    if (strpos($pattern, 'regex:') !== false) {
        $pattern = str_replace('regex:', '', $pattern);

        return preg_match($pattern, $string);
    }

    $pattern = strtr($pattern, [
        '%' => '[\s\S]*',
        '?' => '\?',
        '*' => '\*',
        '+' => '\+',
        '.' => '\.',
    ]);

    return preg_match("/^$pattern$/i", $string);
}

/**
 * Check if WP installed
 */
function giga_wp_exists()
{
    return defined('DB_NAME');
}

if ( ! function_exists('sd')) {
    function sd($object)
    {
        echo '<pre>';
        print_r($object);
        exit;
    }
}

if ( ! function_exists('cl')) {
    function cl($content)
    {
        file_put_contents(GigaAI\Core\Config::get('cache_path') . 'log.txt', print_r($content, true));
    }
}

/**
 * Recursive filter array elements and remove empty key => value pairs.
 *
 * @param array $array
 *
 * @return array
 */
function giga_array_filter(array $array)
{
    $output = [];

    foreach ($array as $key => $value) {

        if (is_null($value) || empty($value)) {
            continue;
        }

        if (is_array($value)) {
            $output[$key] = giga_array_filter($value);
        } else {
            $output[$key] = $value;
        }
    }

    return $output;
}

/**
 * Replace all occurrence from array
 *
 * @param String $key Key to replace
 * @param Mixed  $replace Value to replace
 * @param array  $array Array to replace
 *
 * @return array
 */
function giga_array_replace($key, $replace, $array)
{
    foreach ($array as $array_key => &$value) {
        if ($key === $array_key) {
            $value = $replace;
        } elseif (is_array($value) && ! empty($value)) {
            $value = giga_array_replace($key, $replace, $value);
        }
    }

    return $array;
}

/**
 * Sanitize buttons from Button, List, Generic, Receipt template
 *
 * @param $array
 *
 * @return mixed
 */
function giga_sanitize_button($array)
{
    foreach ($array as $key => &$value) {
        if ($key === 'buttons') {
            $value = array_map(function ($button) {

                if (in_array($button['type'], ['web_url', 'account_link', 'account_unlink', 'element_share'])) {
                    unset($button['payload']);
                }

                if (in_array($button['type'], ['postback', 'phone_number', 'account_unlink', 'element_share'])) {
                    unset($button['url']);
                    unset($button['messenger_extensions']);
                    unset($button['webview_height_ratio']);
                    unset($button['fallback_url']);
                }

                if (in_array($button['type'], ['account_link', 'account_unlink', 'element_share'])) {
                    unset($button['title']);
                }

                if ($button['type'] === 'account_link' && ! isset($button['url'])) {
                    $button['url'] = \GigaAI\Core\Config::get('account_linking_url', '');
                }

                return $button;
            }, $value);
        } elseif (is_array($value) && ! empty($value)) {
            $value = giga_sanitize_button($value);
        }
    }

    return $array;
}

if ( ! function_exists('camel_to_slug')) {
    function camel_to_slug($camel)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $camel));
    }
}

function command_parser_callback($content)
{
    if (isset($content['command'])) {
        $command = $content['command'];
        $args    = $content['args'];

        GigaAI\Core\Command::run($command, $args);
    }
}