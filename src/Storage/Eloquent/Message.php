<?php

namespace GigaAI\Storage\Eloquent;

class Message extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_messages';

    protected $fillable = ['instance_id', 'to_lead', 'to_channel', 'content', 'description',
        'status', 'notification_type', 'send_limit', 'sent_count', 'routines', 'unique_id',
        'created_at', 'updated_at', 'start_at', 'end_at', 'sent_at',
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'start_at',
        'end_at',
        'sent_at'
    ];

    public function getSendLimitAttribute($value)
    {
        return ( ! empty($value)) ? $value : 1;
    }

    public function setContentAttribute($value)
    {
        if (is_array($value))
            $this->attributes['content'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        else
            $this->attributes['content'] = $value;
    }

    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }
}