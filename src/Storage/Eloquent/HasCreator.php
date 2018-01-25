<?php

namespace GigaAI\Storage\Eloquent;

trait HasCreator
{
    public function creator()
    {
        return $this->belongsTo('App\User', 'creator_id', 'id');
    }
}
