<?php

namespace GigaAI\Message;

class Generic extends Message
{
    public function expectedFormat()
    {
        return is_array($this->body) && ! empty($this->body[0]) &&
               is_array($this->body[0]) && array_key_exists('title', $this->body[0])
               && ! array_key_exists('buttons', $this->body);
    }

    public function normalize()
    {
        return [
            'type'    => 'generic',
            'content' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => [
                        'elements'      => $this->body,
                        'template_type' => 'generic',
                    ],
                ],
            ],
        ];
    }
}