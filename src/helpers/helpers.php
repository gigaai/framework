<?php

function giga_remote_post($url, $args = array())
{
	return GigaAI\Http\Http::post($url, $args);
}

function giga_remote_get($url)
{
	return file_get_contents($url);
}

function giga_remote_delete($url, $args = array())
{
	return GigaAI\Http\Http::delete($url, $args);
}

/**
 * Match user entered text with bot pattern
 *
 * @param  String $pattern Pattern
 * @param  String $string  User Text
 * 
 * @return bool
 */
function giga_match($pattern, $string)
{
	if (strpos($pattern, 'regex:') !== false)
	{
		$pattern = str_replace('regex:', '', $pattern);

		return preg_match($pattern, $string);
	}

	$pattern = strtr($pattern, array(
		'%' => '[\s\S]*',
		'?' => '\?',
		'*' => '\*',
		'+' => '\+',
		'.' => '\.'
	));

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