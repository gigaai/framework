<?php

namespace GigaAI\Message;

class Receipt extends Message
{
    public function expectedFormat()
    {
        return is_array($this->body) && isset($this->body['order_number']);
    }

    public function normalize()
    {
        $this->body['template_type'] = 'receipt';

        return [
            'type'    => 'receipt',
            'content' => [
                'attachment' => [
                    'type'    => 'template',
                    'payload' => $this->body,
                ],
            ],
        ];
    }
}