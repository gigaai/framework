<?php

namespace GigaAI\Shortcodes;

use GigaAI\Conversation\Conversation;

class Input
{
    public function output()
    {
        return Conversation::get('received_input');
    }
}