<?php

namespace GigaAI\Storage\Eloquent;

trait HasMeta
{
    public function setMeta($key, $value)
    {
        $meta       = $this->meta;
        $meta[$key] = $value;
        $this->meta = $meta;

        return $this->save();
    }

    public function getMeta($key, $default)
    {
        return isset($this->meta[$key]) ? $this->meta[$key] : $default;
    }
}