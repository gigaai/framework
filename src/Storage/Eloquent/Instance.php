<?php

namespace GigaAI\Storage\Eloquent;

use GigaAI\Conversation\Conversation;
use GigaAI\Core\Config;

class Instance extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_instances';

    protected $fillable = ['id', 'name', 'meta', 'status'];
    
    public $timestamps = false;
    
    public function setMetaAttribute($value)
    {
        if (is_array($value))
            $this->attributes['meta'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        else
            $this->attributes['meta'] = $value;
    }

    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }
    
    public static function get($key, $default = null, $page_id = null)
    {
        if (is_null($page_id)) {
            $page_id = Conversation::get('page_id');
        }
        
        if (is_null($page_id)) {
            return Config::get($key, $default);
        }
        
        $instance = self::find($page_id);
        
        if (isset($instance->meta[$key])) {
            return $instance->meta[$key];
        }
        
        return Config::get($key, $default);
    }
    
    public static function set($key, $value)
    {
        $page_id = Conversation::get('page_id');
    
        if (is_null($page_id)) {
            return;
        }
        
        $instance = self::find($page_id);
        $instance->meta[$key] = $value;
        $instance->save();
    }
}