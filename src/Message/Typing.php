<?php

namespace GigaAI\Message;

class Typing extends Message
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
        $content = is_array($this->body) && isset($this->body['content']) ? $this->body['content'] : $this->body;

        return [
            'type'    => 'typing',
            'content' => $content
        ];
    }
}
