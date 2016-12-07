<?php

namespace GigaAI\Message;

class Text extends AbstractMessage
{
    public function expectedFormat()
    {
        return is_string($this->body);
    }

    public function normalize()
    {
        return [
            'text' => $this->body
        ];
    }
}