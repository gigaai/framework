<?php

namespace GigaAI\Storage\Eloquent;

trait HasMeta
{
    /**
     * Set meta data
     * 
     * @param String $key Meta Key
     * @param mixed $value Meta Value
     * 
     * @return bool
     */
    public function setMeta($key, $value)
    {
        $meta = $this->meta;
        $meta[$key] = $value;
        $this->meta = $meta;

        return $this->save();
    }

    /**
     * Get Meta Data
     * 
     * @param String $key Meta Key
     * @param Mixed $default Default value
     * 
     * @return mixed
     */
    public function getMeta($key = null, $default = null)
    {
        if ($key === null) {
            return $this->meta;
        }

        return isset($this->meta[$key]) ? $this->meta[$key] : $default;
    }

    /**
     * Get/Set meta data
     * 
     * @param String $key Meta key
     * @param mixed $value Meta value, if null then get the data, otherwise, set the data
     * 
     * @return mixed
     */
    public function meta($key = null, $value = null)
    {
        if (is_null($value)) {
            return $this->getMeta($key);
        }

        return $this->setMeta($key, $value);
    }

    /**
     * Get/Set the field or meta data
     * 
     * @param String $field Field name
     * @param mixed $value, if null then get the field or meta data, otherwise set the field or meta data
     * 
     * @return mixed
     */
    public function data($field, $value = null)
    {
        if (is_null($value)) {
            return in_array($field, $this->getFillable()) ? $this->$field : $this->meta[$field];
        }

        if (in_array($field, $this->getFillable())) {
            $this->$field = $value;

            return $this->save();
        }

        return $this->setMeta($field, $value);
    }
}