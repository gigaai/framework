<?php

namespace GigaAI\Storage\Eloquent;

class Message extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_messages';

    protected $fillable = ['instance_id', 'to_lead', 'to_channel', 'content', 'description',
        'status', 'multiple', 'routines', 'unique_id',
        'created_at', 'start_at', 'end_at', 'sent_at',
    ];
}