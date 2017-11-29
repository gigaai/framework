<?php

namespace GigaAI\Shortcodes;

use GigaAI\Conversation\Conversation;
use GigaAI\Storage\Storage;
use GigaAI\Storage\Eloquent\Lead as LeadModel;

class Lead
{
    public $attributes = [];

    public $content = null;

    public function output()
    {
        $lead = Conversation::get('lead');

        if (isset($this->attributes['id'])) {
            $id   = $this->attributes['id'];
            $lead = LeadModel::whereUserId($id)->first();
        }

        foreach ($this->attributes as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            return $lead->data($key, $value);
        }
    }
}