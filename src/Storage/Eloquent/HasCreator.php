<?php

namespace GigaAI\Storage\Eloquent;

trait HasCreator
{
    public function creator()
    {
        $userModel = is_inside_wp() ? WPUser::class : 'App\User';

        return $this->belongsTo($userModel, 'creator_id', 'id');
    }
}
