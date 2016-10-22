<?php

namespace GigaAI\Storage\Eloquent;

class Instance extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_instances';

    protected $fillable = ['id', 'name', 'meta', 'status'];
}