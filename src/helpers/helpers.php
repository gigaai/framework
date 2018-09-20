<?php

function giga_remote_post($url, $args = [])
{
    return GigaAI\Http\Http::post($url, $args);
}

function giga_facebook_url($url)
{
    $accessToken = \GigaAI\Core\Config::get('access_token');
    $mark        = str_contains($url, '?') ? '&' : '?';

    return strtr('https://graph.facebook.com/v2.11/<URL><MARK>access_token=<PAGE_ACCESS_TOKEN>', [
        '<URL>'               => $url,
        '<PAGE_ACCESS_TOKEN>' => $accessToken,
        '<MARK>'              => $mark
    ]);
}

function giga_facebook_post($url, $data, $success = null, $error = null)
{
    $url = giga_facebook_url($url);

    return GigaAI\Http\Http::post($url, $data);
}

function giga_remote_get($url)
{
    return GigaAI\Http\Http::get($url);
}

function giga_facebook_get($url)
{
    $url = giga_facebook_url($url);

    return GigaAI\Http\Http::get($url);
}

function giga_remote_delete($url, $args = [])
{
    return GigaAI\Http\Http::delete($url, $args);
}

function giga_facebook_delete($url, $args = [])
{
    $url = giga_facebook_url($url);

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

if (!function_exists('sd')) {
    function sd($object)
    {
        echo '<pre>';
        print_r($object);
        exit;
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
        } elseif (is_array($value) && !empty($value)) {
            $value = giga_array_replace($key, $replace, $value);
        }
    }

    return $array;
}

if (!function_exists('camel_to_slug')) {
    function camel_to_slug($camel)
    {
        return strtolower(preg_replace('/(?<!^)[A-Z]/', '-$0', $camel));
    }
}

function command_parser_callback($content)
{
    if (isset($content['command'])) {
        $command = $content['command'];
        $args    = isset($content['args']) ? $content['args'] : [];
        GigaAI\Core\Command::run($command, $args);
    }
}

if (! function_exists('is_inside_wp')) {
    function is_inside_wp()
    {
        return defined('DB_HOST');
    }
}
