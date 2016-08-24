<?php

namespace GigaAI\Storage\Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    public $table = 'bot_leads';

    protected $fillable = ['source', 'user_id', 'first_name', 'last_name', 'profile_pic',
        'locale', 'timezone', 'gender', 'email', 'phone', 'country', 'location', '_wait',
        'linked_account', 'subscribe', 'auto_stop'];
}