<?php

namespace GigaAI\Storage\Eloquent;

class Lead extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_leads';

    protected $fillable = ['source', 'user_id', 'first_name', 'last_name', 'profile_pic',
        'locale', 'timezone', 'gender', 'email', 'phone', 'country', 'location', 'wait',
        'linked_account', 'subscribe', 'auto_stop'];
}
