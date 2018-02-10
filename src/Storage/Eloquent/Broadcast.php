<?php

namespace GigaAI\Storage\Eloquent;

use Illuminate\Database\Eloquent\Model;
use Carbon\Carbon;
use GigaAI\Broadcast\Broadcast as BroadcastManager;
use App\ForOwner;

class Broadcast extends Model
{
    use ForOwner;

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

    /**
     * Query Scope to get Broadcast only (without notifications)
     */
    public function scopeIsBroadcast($query)
    {
        return $query->where('parent_id', 0)->orWhere('parent_id', null);
    }

    /**
     * Query scope search
     *
     * @param $query
     * @param $value
     *
     * @return mixed
     */
    public function scopeSearch($query, $value)
    {
        if (!empty($value)) {
            return $query->where('description', 'like', '%' . $value . '%');
        }

        return $query;
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

    public function instance()
    {
        return $this->page();
    }

    public function getReceivers()
    {
        if ( is_array($this->receivers) && ! empty($this->receivers)) {
            return Group::whereIn('id', $this->receivers)->pluck('name', 'id');
        }

        return null;
    }

    public function creator()
    {
        return $this->belongsTo('App\User', 'creator_id', 'id');
    }

    /**
     * Get active broadcast
     * 
     * @return $query
     */
    public function scopeStillActive($query)
    {
        $query->where(function ($query) {
            return $query->where('start_at', '<=', Carbon::now())->orWhereNull('start_at');
        })
        ->where(function ($query) {
            return $query->where('end_at', '>=', Carbon::now())->orWhereNull('end_at');
        })
        ->where(function ($query) {
            return $query->where('parent_id', 0)->orWhereNull('parent_id');
        });

        return $query;
    }

    /**
     * Send Broadcast
     */
    public function send()
    {
        $page = Instance::find($this->instance_id);
        \GigaAI\Core\Config::set('access_token', $page->access_token);

        $message_creative_id = BroadcastManager::createMessageCreative($this);

        if (is_string($message_creative_id)) {
            $this->message_creative_id = $message_creative_id;
            $this->save();
        }

        BroadcastManager::send($this);
    }

    public function getMetrics()
    {
        return BroadcastManager::getMetrics($this);
    }
}