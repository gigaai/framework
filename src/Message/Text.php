<?php

namespace GigaAI\Message;

class Text extends Message
{
    public function expectedFormat()
    {
        return ! isset($this->body['buttons']) && (is_string($this->body) || isset($this->body['text']) || is_string($this->body[0]));
    }

    public function normalize()
    {
        $text = '';

        if (is_string($this->body)) {
            $text = $this->body;
        }

        if (is_array($this->body) && isset($this->body[0]) && is_string($this->body[0])) {
            $text = $this->body[0];
        }

        if (isset($this->body['text'])) {
            $text = $this->body['text'];
        }

        $output = [
            'type'    => 'text',
            'content' => [
                'text' => $text,
            ],
        ];

        if (isset($this->body['quick_replies'])) {
            $output['content']['quick_replies'] = $this->body['quick_replies'];
        }

        return $output;
    }
}