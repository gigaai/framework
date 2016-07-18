<?php
/**
 * Facebook Messenger Bots
 *
 * @version 4.0 Giga
 */

// Environments
ini_set( 'allow_url_fopen', true );

// Constants
define( 'GIGA_ABS_PATH', dirname( __FILE__ ) . '/' );
define( 'GIGA_STORAGE_PATH', GIGA_ABS_PATH . '/storage/' );
define( 'GIGA_CACHE_PATH', GIGA_ABS_PATH . '/cache/' );

// Helpers
require_once GIGA_ABS_PATH . 'config.php';
require_once GIGA_ABS_PATH . 'core/Http.php';
require_once GIGA_ABS_PATH . 'helpers/helpers.php';

// Storage
require_once GIGA_STORAGE_PATH . 'StorageInterface.php';
require_once GIGA_STORAGE_PATH . 'Storage.php';

// Main
require_once GIGA_ABS_PATH . 'core/Parser.php';
require_once GIGA_ABS_PATH . 'core/Request.php';
require_once GIGA_ABS_PATH . 'core/Model.php';
require_once GIGA_ABS_PATH . 'core/MessengerBot.php';