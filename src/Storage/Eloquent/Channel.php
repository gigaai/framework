<?php

namespace GigaAI\Storage\Eloquent;

use GigaAI\Storage\Eloquent\ChannelScope;
use Illuminate\Database\Eloquent\Builder;

class Channel extends Group
{
    /**
     * The "booting" method of the model.
     *
     * @return void
     */
    protected static function boot()
    {
        parent::boot();

        static::addGlobalScope('channel', function (Builder $builder) {
            $builder->where('type', 'channel');
        });
    }
}