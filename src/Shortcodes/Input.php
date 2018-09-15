<?php

namespace GigaAI\Shortcodes;

use GigaAI\Conversation\Conversation;

/**
 * The [input] shortcode, it's only return the received input
 *
 * @package GigaAI\Shortcodes
 */
class Input
{
    public function output()
    {
        return Conversation::get('received_input');
    }
}