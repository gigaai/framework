<?php

namespace GigaAI\Storage\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use App\ForOwner;
use GigaAI\Storage\Eloquent\HasCreator;

class Lead extends Model
{
    use SoftDeletes, HasMeta, HasCreator, ForOwner;

    public $table = 'giga_leads';

    protected $fillable = [
        'creator_id',
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
    public function scopeSearch($query, $request)
    {
        if ( ! empty($request->s)) {
            $query->where('first_name', 'like', '%' . $request->s . '%')
                    ->orWhere('last_name', 'like', '%' . $request->s . '%')
                    ->orWhere('email', 'like', '%' . $request->s . '%')
                    ->orWhere('phone', 'like', '%' . $request->s . '%');
        }

        if (isset($request->status) && $request->status === 'trashed') {
            $query->onlyTrashed();
        }

        return $query;
    }

    public function scopeNotIn($query, $value)
    {
        if ( ! empty($value)) {
            if (!is_array($value)) {
                $value = explode(',', $value);
            }

            return $query->whereNotIn('id', $value);
        }

        return $query;
    }

    /**
     * Laravel linked user
     *
     * @return App\User
     */
    public function linkedUser()
    {
        return $this->hasOne('App\User', 'id', 'linked_account');
    }

    public function user()
    {
        return $this->linkedUser()->first();
    }
    
    public function channels()
    {
        return $this->morphToMany(Group::class, 'giga_groupable');
    }

    public function instance()
    {
        return $this->belongsTo(Instance::class, 'source', 'id');
    }
}
