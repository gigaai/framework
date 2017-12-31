<?php

namespace GigaAI\Message;

class Button extends Message
{
    public function expectedFormat()
    {
        return is_array($this->body) && isset($this->body['buttons'])
               && !array_key_exists('title', $this->body)
               && !array_key_exists('elements', $this->body);
    }

    public function normalize()
    {
        if (isset($this->body['attachment'])) {
            $content = $this->body;
        } else {
            $this->body['template_type'] = 'button';
            $this->body['buttons']       = $this->sanitizeButtons($this->body['buttons']);

            $content = [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => $this->body,
                ],
            ];
        }

        return [
            'type'    => 'button',
            'content' => $content
        ];
    }
}
