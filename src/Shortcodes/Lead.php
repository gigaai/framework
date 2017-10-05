<?php

namespace GigaAI\Shortcodes;

use GigaAI\Conversation\Conversation;
use GigaAI\Storage\Storage;

class Lead
{
    public $attributes = [
        'field' => null,
        'id'    => null,
        'email' => null,
        'phone' => null,
    ];

    public $content = null;

    public function output()
    {
        $lead_id = Conversation::get('lead_id');

        if (isset($this->attributes['id'])) {
            $lead_id = $this->attributes['id'];
        }

        $field = $this->attributes['field'];
        return Storage::get($lead_id, $field);
    }
}