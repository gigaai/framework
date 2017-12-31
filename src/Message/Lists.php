<?php

namespace GigaAI\Message;

class Lists extends Message
{
    public function expectedFormat()
    {
        return is_array($this->body) && array_key_exists('elements', $this->body) && !array_key_exists(
            'order_number',
                $this->body
        );
    }

    public function normalize()
    {
        if (isset($this->body['attachment'])) {
            $content = $this->body;
        } else {
            $this->body['template_type'] = 'list';

            if (!isset($this->body['top_element_style'])) {
                $this->body['top_element_style'] = 'large';
            }

            $content = [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => $this->body
                ],
            ];
        }

        $content['attachment']['payload']['elements'] = $this->sanitizeElements($content['attachment']['payload']['elements']);

        return [
            'type'    => 'list',
            'content' => $content
        ];
    }
}
