<?php

namespace GigaAI\Storage\Eloquent;

class Message extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_messages';

    protected $fillable = ['instance_id', 'to_lead', 'to_channel', 'content', 'description',
        'status', 'send_limit', 'sent_count', 'routines', 'unique_id',
        'created_at', 'updated_at', 'start_at', 'end_at', 'sent_at',
    ];

    protected $casts = [
        'to_channel' => 'array',
        'content'    => 'array'
    ];

    protected $dates = [
        'created_at',
        'updated_at',
        'start_at',
        'end_at',
        'sent_at'
    ];

    public function getMultipleAttribute($value)
    {
        return ( ! empty($value)) ? $value : 1;
    }
}