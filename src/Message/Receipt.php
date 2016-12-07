<?php

namespace GigaAI\Message;

class Receipt extends AbstractMessage
{
    public function expectedFormat()
    {
        return is_array($this->body) && isset($this->body['order_number']);
    }

    public function normalize()
    {
        $this->body['template_type'] = 'receipt';

        return [
            'attachment' => [
                'type' => 'template',
                'payload' => $this->body
            ]
        ];
    }
}