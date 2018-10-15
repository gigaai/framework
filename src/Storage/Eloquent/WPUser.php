<?php

namespace GigaAI\Storage\Eloquent;

use Illuminate\Database\Eloquent\Model;
use GigaAI\Storage\Eloquent\Group;
use GigaAI\Storage\Eloquent\Instance;
use GigaAI\Facebook\Facebook;

class WPUser extends Model
{
    protected $table = 'users';

    protected $primaryKey = 'ID';

    protected $fillable = [
        'ID', 
        'user_login',
        'user_pass',
        'user_nicename',
        'user_email',
        'user_url',
        'user_registered',
        'user_activation_key',
        'user_status',
        'display_name'
    ];

    protected $hidden = [
        'user_pass',
        'user_activation_key'
    ];

    public function data($field, $value = null)
    {
        if (is_null($value)) {
            return in_array($field, $this->getFillable()) ? $this->$field : get_user_meta($this->ID, $field, true);
        }

        if (in_array($field, $this->getFillable())) {
            $this->$field = $value;

            return $this->save();
        }

        return update_user_meta($this->ID, $field, $value);
    }

    /**
     * User Groups Relation
     *
     * @return \Illuminate\Database\Eloquent\Relations\MorphToMany
     */
    public function groups()
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
    public function getAvatarAttribute()
    {
        return get_avatar_url($this->ID);
    }

    public static function current()
    {
        $userId = get_current_user_id();

        return self::find($userId);
    }

    public function isAdmin()
    {
        return user_can($this->ID, 'administrator');
    }

    /**
     * Check if current user in group by id or slug
     *
     * @param Integer/String $slug
     *
     * @return bool
     */
    public function inGroup($slug)
    {
        return $this->groups->contains(function ($group) use ($slug) {
            return $group->slug === $slug || $group->id === $slug;
        });
    }

    public function isConnectedToFacebook()
    {
        $accessToken= $this->data('access_token');
        $providerId = $this->data('provider_id');

        return ! empty($accessToken) && ! empty($providerId);
    }

      /**
     * Get Facebook Pages of current user if connected to Facebook
     *
     * @return mixed
     */
    public function getFacebookPages()
    {
        // Returns null if they haven't connected to Facebook
        if ($this->isConnectedToFacebook() !== true) {
            return null;
        }

        $response = Facebook::load()->get('/me/accounts', $this->data('access_token'))->getDecodedBody();

        $pages = collect($response['data'])->keyBy('id');

        // Returns the list of pages.
        return $pages;
    }

    public function instances()
    {
        return $this->hasMany(Instance::class, 'creator_id', 'ID');
    }
}
