<?php

namespace GigaAI\Storage\Eloquent;

use GigaAI\Subscription\Subscription;
use Illuminate\Database\Eloquent\Model;

class Broadcast extends Model
{
    public $table = 'giga_broadcasts';
    
    protected $fillable = ['instance_id', 'creator_id', 'message_creative_id', 'parent_id',
        'name', 'content', 'description',
        'receiver_type', 'receivers', 'wait', 'status', 'tags',
        'notification_type', 'send_limit', 'sent_count', 'routines',
        'created_at', 'updated_at', 'start_at', 'end_at', 'sent_at', 'meta'
    ];
    
    protected $dates = [
        'created_at',
        'updated_at',
        'start_at',
        'end_at',
        'sent_at',
    ];
    
    protected $casts = [
        'receivers'    => 'json'
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
    
    public function getReceiversAttribute($value)
    {
        return (is_null($value)) ? [] : json_decode($value, true);
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
        if (isset($this->to_lead) && is_array($this->to_lead)) {
            return count($this->to_lead);
        }
        
        return count(Subscription::getSubscribers($this->to_channel));
    }

    public function page()
    {
        return $this->belongsTo(Instance::class, 'instance_id', 'id');
    }

    public function channel()
    {
        return $this->belongsTo('App\Group', 'receivers', 'id');
    }
}