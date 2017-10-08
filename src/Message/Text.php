<?php

namespace GigaAI\Message;

use GigaAI\Core\Parser;

class Text extends Message
{
    public function expectedFormat()
    {
        return is_string($this->body);
    }

    public function normalize()
    {
        $text = Parser::parseShortcodes($this->body);

        return compact('text');
    }
}