<?php
/** 
 * Webhook file. User can access via /messenger/
 */

if ( isset( $_REQUEST['hub_verify_token'] ) && $_REQUEST['hub_verify_token'] == 'GigaAI' )
{
	echo $_REQUEST['hub_challenge'];

	exit;
}

require_once 'loader.php';

$ai = new Giga\MessengerBot;

$ai->answers('subscribe', function ($ai)
{
	$user_id = $ai->sender_id;

	Giga\Storage\Storage::set($user_id, 'unsubscribed', 0);
});

$ai->answer('unsubscribe', function($ai)
{
	$user_id = $ai->sender_id;

	Giga\Storage\Storage::set($user_id, 'unsubscribed', 1);
});

$ai->answer('hi', array(
	'Hi [first_name]. Nice to meet you today!',
	'https://scontent.xx.fbcdn.net/v/t1.0-1/p200x200/1452334_799641690141525_6888322228387702964_n.jpg?oh=dd36f65aabcec78fab41f357824ef9bd&oe=582A3A1A'
));

$ai->answer('default:', 'Default message!');

// Hi or Hello
$ai->answer('(hi|hello) there', 'greeting');

// Do you /understand|know/ it?
$ai->answer('Do you (understand|know)%?', 'Foo');
// require_once 'examples/example.php';

$ai->answer('img', 'file:http://sangplus.com/wp-content/uploads/2016/04/Screenshot-32-700x394.png');

$ai->answer('foo', array(
	'Thank you',
	'Please enter password:'
))->wait('password');

$ai->answer('@password', 'You have just enterd password');

$ai->run();