<?php

ini_set('display_startup_errors',1);
ini_set('display_errors',1);
error_reporting(-1);

require_once 'loader.php';

$post 	= giga_remote_post( "https://graph.facebook.com/v2.6/me/subscribed_apps?access_token=" . PAGE_ACCESS_TOKEN );

dd($post);