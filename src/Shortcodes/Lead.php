<?php

namespace GigaAI\Shortcodes;

use GigaAI\Conversation\Conversation;
use GigaAI\Storage\Storage;
use GigaAI\Storage\Eloquent\Lead as LeadModel;

/**
 * The [lead] shortcode
 *
 * Usage:
 * [lead $field] -> Return the field of current lead.
 * [lead $field="$value"] -> Set the field of current lead with the provided value.
 * [lead id="$leadId" ...] -> Find then return or set the field of the lead.
 *
 * @package GigaAI\Shortcodes
 */
class Lead
{
    /**
     * The attributes of the shortcode
     *
     * @var array
     */
    public $attributes = [];

    /**
     * Shortcode content (We don't use in this shortcode)
     *
     * @var null
     */
    public $content = null;

    /**
     * Define the shortcode's output
     *
     * @return mixed
     */
    public function output()
    {
        // By default, the shortcode takes the current lead as the input
        $lead = Conversation::get('lead');

        // If id provided, the shortcode will find the lead with the id.
        if (isset($this->attributes['id'])) {
            $id   = $this->attributes['id'];
            $lead = LeadModel::whereUserId($id)->first();
        }

        // Loop through attributes and get or set data
        foreach ($this->attributes as $key => $value) {
            if ($key === 'id') {
                continue;
            }

            // Note that if $value isn't provided, it will get data.
            return $lead->data($key, $value);
        }
    }
}