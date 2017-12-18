<?php

namespace GigaAI\Message;

class Text extends Message
{
    public function expectedFormat()
    {
        return is_string($this->body);
    }

    public function normalize()
    {
        return [
            'type'    => 'text',
            'content' => [
                'text' => $this->body,
            ],
        ];
    }
}