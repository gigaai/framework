<?php

namespace GigaAI\Message;

class Raw extends Message
{
    public function expectedFormat()
    {
        return true;
    }

    public function normalize()
    {
        return [
            'type'    => 'raw',
            'content' => $this->body
        ];
    }
}
