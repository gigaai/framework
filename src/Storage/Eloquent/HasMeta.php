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

    public function getMeta($key, $default = null)
    {
        return isset($this->meta[$key]) ? $this->meta[$key] : $default;
    }

    public function meta($key, $value = null)
    {
        if (is_null($value)) {
            return $this->getMeta($key);
        }

        return $this->setMeta($key, $value);
    }
}