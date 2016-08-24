<?php

namespace GigaAI\Storage\Eloquent;

class Answer extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_answers';

    protected $fillable = ['pattern', 'answers', 'sources', 'type', 'status'];

    protected $casts = [
        'answers' => 'array'
    ];
}
