<?php

namespace GigaAI\Message;

class Button extends AbstractMessage
{
    public function expectedFormat()
    {
        return is_array($this->body) && isset($this->body['buttons']) && ! array_key_exists('title', $this->body);
    }

    public function normalize()
    {
        $this->body['template_type'] = 'button';

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => $this->body
            ]
        ];
    }
}