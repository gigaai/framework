<?php

namespace GigaAI\Storage\Eloquent;

use Illuminate\Database\Eloquent\Model;

class WPUser extends Model
{
    protected $table = null;

    protected $primaryKey = 'ID';

    protected $fillable = [
        'user_login', 'user_pass', 'user_nicename', 'user_email', 'user_url',
        'user_registered', 'user_activation_key', 'user_status', 'display_name'
    ];

    protected $hidden = ['user_pass'];

    public function __construct(array $attributes = [])
    {
        global $wpdb;

        $this->table = $wpdb->prefix . '_users';

        parent::__construct($attributes);
    }
}
