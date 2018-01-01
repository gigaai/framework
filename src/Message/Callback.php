<?php

namespace GigaAI\Message;

class Callback extends Message
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
        $closure = is_array($this->body) && isset($this->body['content']) ? $this->body['content'] : $this->body;
        
        return [
            'type'    => 'callback',
            'content' => $closure
        ];
    }
}