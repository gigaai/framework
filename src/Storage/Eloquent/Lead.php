<?php

namespace GigaAI\Storage\Eloquent;

use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Database\Eloquent\Model;
use GigaAI\Storage\Eloquent\HasCreator;
use GigaAI\Core\Matching;

class Lead extends Model
{
    use SoftDeletes, HasMeta, HasCreator, ForOwner, UserModel;

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
        'auto_stop',
        'last_activity',
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
        if (! empty($request->s)) {
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
        if (! empty($value)) {
            $value = is_array($value) ? $value : explode(',', $value);

            return $query->whereNotIn('id', $value);
        }

        return $query;
    }

    /**
     * Check if current user is linked
     *
     * @return bool
     */
    public function isLinked()
    {
        return is_numeric($this->linked_account) && $this->linked_account > 0;
    }

    /**
     * Laravel linked user
     *
     * @param $props
     *
     * @return mixed
     */
    public function linkedUser($props = [])
    {
        if (!$this->isLinked() && isset($props['try_matching']) && $props['try_matching'] === true) {
            return Matching::matchCurrentLead();
        }

        return $this->belongsTo($this->getUserModel(), 'linked_account', $this->getUserModelKey());
    }

    /**
     * Linked user relationship
     *
     * @param array $props
     * @return null
     */
    public function user($props = [])
    {
        if (!$this->isLinked() && isset($props['try_matching']) && $props['try_matching'] === true) {
            return Matching::matchCurrentLead();
        }

        return $this->linkedUser()->first();
    }

    /**
     * Relationship with Group
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function channels()
    {
        return $this->morphToMany(Group::class, 'giga_groupable');
    }

    /**
     * Returns user avatar if exists, otherwise, returns default avatar
     *
     * @param $value
     *
     * @return string
     */
    public function getProfilePicAttribute($value)
    {
        if (!empty($value)) {
            return 'data:image/png;base64,' . base64_encode($value);
        }

        return '/img/no-photo.jpg';
    }

    /**
     * Relationship with Instance
     *
     * @return \Illuminate\Database\Eloquent\Relations\BelongsTo
     */
    public function instance()
    {
        return $this->belongsTo(Instance::class, 'source', 'page_id');
    }
}
