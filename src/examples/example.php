<?php

// Welcome Message Template. Includes two buttons
$welcome_message = array(
	'button' => array(
		
		'text' => "Hi mate, thanks god, you are here. Let's start talking man. Do you like cars?",
		
		'buttons' => array(
			// Button can be postback or web url
			array(
				'title' => 'Yes',
				'type' => 'postback',
				'payload' => 'UserClickYes'
			),

			array(
				'title' => 'No',
				'type' => 'postback',
				'payload' => 'UserClickNo'
			)
		)
	)
);


$help_text = array(
	'button' => array(
		'text' => "Let's talk about bot features. Bot can send message in various format, for example, buttons below, and:",
		'buttons' => array(
			array(
				'title' => 'Text',
				'type' => 'postback',
				'payload' => 'UserClickText'
			),

			array(
				'title' => 'Image',
				'type' => 'postback',
				'payload' => 'UserClickImage'
			),

			array(
				'title' => 'Custom Template',
				'type' => 'postback',
				'payload' => 'UserClickCustomTemplate'
			),
		)
	)
);

$cars = array(
	// A generic bubble
	array(
		"title"     => "Lamborghini",
		"image_url" => "http://pictures.topspeed.com/IMG/crop/201603/2016-lamborghini-centenar-5_800x0w.jpg",
		"subtitle"  => "Amazing speed",
		"buttons"   => array(
			array(
				"type"  => "web_url",
				"url"   => "https://lamborghini.com",
				"title" => "Buy Now"
			),
			array(
				"type"    => "postback",
				"payload" => "BuyLamborghiniTomorrow",
				"title"   => "Buy Tomorrow"
			)
		)
	),

	// Another generic bubble
	array(
		"title"     => "Rolls Royce",
		"image_url" => "http://static.robbreport.com/sites/default/files/images/articles/2015Sep/1642581//rolls-royce-dawn-02.jpg",
		"subtitle"  => "Most Luxury Car",
		"buttons"   => array(
			array(
				"type"  => "web_url",
				"url"   => "https://www.rolls-roycemotorcars.com/en-GB/home.html",
				"title" => "Buy Now"
			),
			array(
				"type"    => "postback",
				"payload" => "BuyRollsRoyceTomorrow",
				"title"   => "Buy Tomorrow"
			)
		)
	),

	// Yet another generic bubble
	array(
		"title"     => "Ferrari",
		"image_url" => "https://cdn1.vox-cdn.com/thumbor/-pG8Dcb_qtRf6te3ug12FHhqUDs=/1020x0/cdn0.vox-cdn.com/uploads/chorus_asset/file/4156848/Ferrari_F12tdf_3low.0.jpg",
		"subtitle"  => "I like the horse",
		"buttons"   => array(
			array(
				"type"  => "web_url",
				"url"   => "http://www.ferrari.com/en_en/",
				"title" => "Buy Now"
			),
			array(
				"type"    => "postback",
				"payload" => "BuyFerrariTomorrow",
				"title"   => "Buy Tomorrow"
			)
		)
	),
);

$answers = array(
	// Set welcome message to send when people click "Get started"
	'welcome:' 	=> $welcome_message,
	
	// Set welcome message to send when people say something contains "Hello"
	'%hello%'	=> $welcome_message,

	// When user like cars. Show a generic template. A generic can contains up to three bubbles.
	'payload:UserClickYes' => array(
		// Show the cars list
		'generic' => $cars,
		
		// Continue the message by send a text
		'Cool, now start with more advanced features, type `help` to get help information.'
	),

	'help'		=> $help_text,

	'payload:UserClickText' => array(
		"This is the text message sent by bot.",
		"You can send up to three responses each time",
	),

	'payload:UserClickImage' => array(
		array(
			'type' => 'image',
			'content' => 'https://img0.etsystatic.com/020/0/9066975/il_fullxfull.553914598_pts0.jpg'
		)
	),

	'payload:UserClickCustomTemplate' => array(
		'generic' => $cars
	),

	'%bye%' => "Nice chat. We hope you like this bot",

	'default:' => "Sorry, bot don't understand what you said, this is the default message feature appear when user text message or click on buttons which not defined. Type `help` to continue :)"
);

// Create an instance of FacebookMessengerBot class
$bot = new FacebookMessengerBot( $answers );


// Bot says: "Ding ding ding ding ding ding!" when people ask "What does the fox says?"
$bot->answers('What does the fox says?', 'Ding ding ding ding ding ding!');

// More example in documentation

// Run the bot
$bot->run();