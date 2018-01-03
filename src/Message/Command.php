<?php

namespace GigaAI\Message;

class Command extends Message
{
    /**
     * It should defined with type => content
     */
    public function expectedFormat()
    {
        return false;
    }

    public function normalize()
    {
        $command = is_array($this->body) && isset($this->body['content']) ? $this->body['content'] : $this->body;

        return [
            'type'    => 'command',
            'content' => $command
        ];
    }
}
