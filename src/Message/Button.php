<?php

namespace GigaAI\Message;

class Button extends Message
{
    public function expectedFormat()
    {
        return is_array($this->body) && isset($this->body['buttons'])
               && ! array_key_exists('title', $this->body)
               && ! array_key_exists('elements', $this->body);
    }

    public function normalize()
    {
        $this->body['template_type'] = 'button';

        return [
            'type'    => 'button',
            'content' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => $this->body,
                ],
            ],
        ];
    }
}