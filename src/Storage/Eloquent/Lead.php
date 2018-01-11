<?php

namespace GigaAI\Storage\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;

class Lead extends \Illuminate\Database\Eloquent\Model
{
    use SoftDeletes;

    public $table = 'bot_leads';

    protected $fillable = [
        'source',
        'user_id',
        'first_name',
        'last_name',
        'profile_pic',
        'locale',
        'timezone',
        'gender',
        'email',
        'phone',
        'country',
        'location',
        'birthday',
        '_wait',
        'linked_account',
        'subscribe',
        'is_payment_enabled',
        'auto_stop',

        'last_activity',
        'last_ad_referral',
        'meta',
    ];

    protected $casts = [
        'meta' => 'json',
    ];

    protected $dates = [
        'last_activity',
    ];

    public function getFullName()
    {
        return $this->first_name . ' ' . $this->last_name;
    }

    /**
     * Query scope source
     *
     * @param $query
     * @param $value
     *
     * @return mixed
     */
    public function scopeOfSource($query, $value)
    {
        if (!empty($value)) {
            return $query->where('source', $value);
        }

        return $query;
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
            return $query->where('first_name', 'like', '%' . $value . '%')
                         ->orWhere('last_name', 'like', '%' . $value . '%')
                         ->orWhere('email', 'like', '%' . $value . '%')
                         ->orWhere('phone', 'like', '%' . $value . '%');
        }

        return $query;
    }

    public function scopeNotIn($query, $value)
    {
        if (!empty($value)) {
            if (!is_array($value)) {
                $value = explode(',', $value);
            }

            return $query->whereNotIn('id', $value);
        }

        return $query;
    }

    public function data($field, $value = null)
    {
        if (is_null($value)) {
            return in_array($field, $this->getFillable()) ? $this->$field : $this->meta[$field];
        }

        if (in_array($field, $this->getFillable())) {
            $this->$field = $value;

            return $this->save();
        } else {
            return $this->setMeta($field, $value);
        }
    }

    /**
     * Laravel linked user
     *
     * @return \Illuminate\Database\Eloquent\Relations\HasOne
     */
    public function linkedUser()
    {
        return $this->hasOne('App\User', 'id', 'linked_account');
    }

    public function channels()
    {
        return $this->morphToMany('App\Group', 'groupable');
    }
}
