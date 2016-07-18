<?php

function giga_remote_post($url, $args = array())
{
	if (function_exists('wp_remote_post'))
		return wp_remote_post($url, $args);

	if ( ! empty( $args['body'] ) )
		return GigaAI\Http\Http::post($url, $args['body']);

	return GigaAI\Http\Http::post($url);
}

function giga_remote_get($url, $args = array())
{
	if (function_exists('wp_remote_get'))
		return wp_remote_get($url, $args);

	return file_get_contents($url);
}


function giga_match($pattern, $string)
{
	if (strpos($pattern, 'regex:') !== false)
	{
		$pattern = str_replace('regex:', '', $pattern);

		return preg_match($pattern, $string);
	}

	$pattern = str_replace('%', "[\s\S]*", $pattern);

	return preg_match("/$pattern$/i", $string);
}

if ( ! function_exists( 'dd' ) )
{
	function dd($object)
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