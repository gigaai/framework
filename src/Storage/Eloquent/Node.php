<?php

namespace GigaAI\Storage\Eloquent;

class Node extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_nodes';

    protected $fillable = ['pattern', 'answers', 'wait', 'sources', 'type', 'status'];

    protected $casts = [
        'answers' => 'array'
    ];
}
