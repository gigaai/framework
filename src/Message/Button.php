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

            $content = [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => $this->body,
                ],
            ];
        }

        $content['attachment']['payload']['buttons'] = $this->sanitizeButtons($content['attachment']['payload']['buttons']);

        return [
            'type'    => 'button',
            'content' => $content
        ];
    }
}
