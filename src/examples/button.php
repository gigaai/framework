<?php

/**
 * Define Buttons
 */

// Single Button Group
$bot->answers('foo', array(
	'text' => 'Please click on one of those buttons',
	'buttons' => array(
		array(
			'title' => 'Button 1',
			'type' => 'web_url',
			'url'   => 'https://google.com'
		),
		array(
			'title' => 'Button 2',
			'type' => 'postback',
			'payload' => 'BUTTON_2_CLICKED_EVENT'
		)
	)
));

// Multiple Button Group. Just nested them in array.
$bot->answers('bar', array(
	array(
		'text' => 'Please click on one of those buttons',
		'buttons' => array(
			array(
				'title' => 'Button 1',
				'type' => 'web_url',
				'url'   => 'https://google.com'
			),
			array(
				'title' => 'Button 2',
				'type' => 'postback',
				'payload' => 'BUTTON_2_CLICKED_EVENT'
			)
		)
	),
	array(
		'text' => 'Please click on one of those buttons',
		'buttons' => array(
			array(
				'title' => 'Button 1',
				'type' => 'web_url',
				'url'   => 'https://google.com'
			),
			array(
				'title' => 'Button 2',
				'type' => 'postback',
				'payload' => 'BUTTON_2_CLICKED_EVENT'
			)
		)
	)
));

/**
 * Handle Button Postback
 **/

// Response with simple text or any message type
$bot->answers('payload:BUTTON_2_CLICKED_EVENT', 'Thanks for clicking to that button');

// Advanced. Do something on server side and return dynamic data
$bot->answers('payload:BUTTON_2_CLICKED_EVENT', function ($bot) {

	// Do anything you want here
	// For example. Process a payment.

	// You can response instead of answer. Just pass 2nd parameter of answer and use response method
	$bot->response('Action succeed!');
});