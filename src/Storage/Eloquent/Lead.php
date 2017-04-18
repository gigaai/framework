<?php

namespace GigaAI\Storage\Eloquent;
use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;
    
    public $table = 'bot_leads';
    
    protected $fillable = ['instance_id', 'source', 'user_id', 'first_name', 'last_name', 'profile_pic',
        'locale', 'timezone', 'gender', 'email', 'phone', 'country', 'location', '_wait', '_quick_save',
        'linked_account', 'subscribe', 'is_payment_enabled', 'auto_stop'];
    
    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }
    
    /**
     * Query scope source
     *
     * @param $query
     * @param $value
     * @return mixed
     */
    public function scopeOfSource($query, $value)
    {
        if ( ! empty($value))
            return $query->where('source', $value);
        
        return $query;
    }
    
    /**
     * Query scope search
     *
     * @param $query
     * @param $value
     * @return mixed
     */
    public function scopeSearch($query, $value)
    {
        if ( ! empty($value))
            return $query->where('first_name', 'like', '%' . $value . '%')
                ->orWhere('last_name', 'like', '%' . $value . '%')
                ->orWhere('email', 'like', '%' . $value . '%')
                ->orWhere('phone', 'like', '%' . $value . '%');
        
        return $query;
    }
}