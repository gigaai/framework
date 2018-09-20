<?php

namespace GigaAI\Shortcodes;

use GigaAI\Conversation\Conversation;


/**
 * [nlp] Shortcode
 * 
 * Example: [nlp filter="#email:first" field="value"]
 */
class Nlp
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

    public function output()
    {
        $nlp = Conversation::get('nlp');
        
        $filter = isset($this->attributes['filter']) ? $this->attributes['filter'] : ':first';
        $field = isset($this->attributes['field']) ? $this->attributes['field'] : 'value';

        return $nlp->filter($filter)->get($field);
    }
}