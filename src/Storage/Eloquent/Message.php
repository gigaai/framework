<?php

namespace GigaAI\Storage\Eloquent;

use GigaAI\Subscription\Subscription;

class Message extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_messages';
    
    protected $fillable = ['instance_id', 'to_lead', 'to_channel', 'content', 'description',
        'status', 'notification_type', 'send_limit', 'sent_count', 'routines', 'unique_id',
        'created_at', 'updated_at', 'start_at', 'end_at', 'sent_at', 'wait'
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'start_at',
        'end_at',
        'sent_at',
    ];
    
    public function getSendLimitAttribute($value)
    {
        return ( ! empty($value)) ? $value : 0;
    }
    
    public function setContentAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['content'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            $this->attributes['content'] = $value;
        }
    }
    
    /**
     * Auto json decode the content attribute
     *
     * @param $value
     * @return mixed
     */
    public function getContentsAttribute($value)
    {
        return json_decode($value, true);
    }
    
    /**
     * Set To Channel Attribute
     *
     * @param String $value
     * @return void
     */
    public function setToChannelAttribute($value)
    {
        if (is_array($value)) {
            $this->attributes['to_channel'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            $this->attributes['to_channel'] = $value;
        }
    }
    
    public function setUniqueIdAttribute($value)
    {
        if (empty($value)) {
            $this->attributes['unique_id'] = $this->attributes['id'];
        } else {
            $this->attributes['unique_id'] = $value;
        }
    }
    
    public function getUniqueIdAttribute($value)
    {
        return ( ! empty($value)) ? $value : $this->attributes['id'];
    }
    
    public function getContentAttribute($value)
    {
        return json_decode($value, true);
    }
    
    /**
     * Get total leads of a channel
     *
     * @return void
     */
    public function leadsCount()
    {
        if (isset($this->to_lead)) {
            return substr_count($this->to_lead, ',');
        }
        
        return count(Subscription::getSubscribers($this->to_channel));
    }
}