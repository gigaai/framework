<?php

namespace GigaAI\Storage\Eloquent;

class Instance extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_instances';

    protected $fillable = ['id', 'name', 'meta', 'status'];

    public function setMetaAttribute($value)
    {
        if (is_array($value))
            $this->attributes['meta'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        else
            $this->attributes['meta'] = $value;
    }

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
}