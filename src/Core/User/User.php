<?php


namespace GigaAI\Core\User;


use Illuminate\Database\Eloquent\Model;

class User extends Model
{
    public $fillable = [
        'id',
        'source'.
        'user_id',
        'first_name',
        'last_name',
        'profile_pic',
        'locale',
        'timezone',
        'gender',
        'email',
        'phone',
        'country',
        'location',
        'wait',
        'quick_save',
        'linked_account',
        'subscribe',
        'auto_stop',
        'created_at',
        'updated_at',
        'deleted_at'
    ];
}