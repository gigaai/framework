<?php

namespace GigaAI\Storage\Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    public $table = 'bot_leads';

    protected $fillable = ['instance_id', 'source', 'user_id', 'first_name', 'last_name', 'profile_pic',
        'locale', 'timezone', 'gender', 'email', 'phone', 'country', 'location', '_wait', '_quick_save',
        'linked_account', 'subscribe', 'is_payment_enabled', 'auto_stop'];
}