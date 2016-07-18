<?php

// Response with Simple Text
$bot->answers('hi', 'Hi there!');

// Multiple Response per Node
$bot->answers('tell me about you', array(
	'I am 3 days old',
	'A Smart Robot',
	'Which powerful'
));

// With Shortcode
$bot->answers('hello', 'Hello [first_name] [last_name]');

/**------------------------------------------------------
 * Advanced Text Matching
 *--------------------------------------------------------*/

// SQL Like Syntax
// This matches with any string which contains `buy` then following by `gun`.
// For example: 'I want to buy this gun'
$bot->answers('%buy%gun%', 'Which gun do you plan to buy?');

// Match one of those text
$bot->answers('(hi|hello)', 'This matches with `hi` or `hello`');

// Combine two above features
$bot->answers('%(hi|hello)%', 'Hi');

/**
 * Same answer for multiple Event
 */

// Todo: Support this syntax
// User ask `foo` or `bar`. Answer with 'Hello'
$bot->answers(array(
	'foo', 'bar'
), 'Hello');

$bot->answers(array(
	'foo',
	'payload:THIS_IS_A_PAYLOAD'
), 'Hello');

// Shorthand