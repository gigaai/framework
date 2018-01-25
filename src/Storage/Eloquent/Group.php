<?php

namespace GigaAI\Storage\Eloquent;

use Illuminate\Database\Eloquent\Model;
use GigaAI\Storage\Eloquent\Lead;
use GigaAI\Storage\Eloquent\HasMeta;

class Group extends Model
{
    use HasCreator, HasMeta;

    protected $fillable = ['name', 'creator_id', 'slug', 'description', 'type', 'parent_id', 'permissions', 'meta'];

    protected $casts = [
        'permissions' => 'json',
        'meta'        => 'json'
    ];

    public function hasPermission($permission)
    {
        return (is_array($this->permissions) &&
                isset($this->permissions[$permission]) &&
                $this->permissions[$permission] == true
               ) ||
               (
                   isset($this->permissions['administrator']) &&
                $this->permissions['administrator'] == true
               );
    }

    public function users()
    {
        return $this->morphedByMany('App\User', 'groupable');
    }

    public function leads()
    {
        return $this->morphedByMany(Lead::class, 'groupable');
    }

    // Class Post extends Content
    public function posts()
    {
        //
    }

    public function getSlugAttribute($value)
    {
        if (empty($value)) {
            $value = str_slug($this->name);
        }

        return $value;
    }

    public function setSlugAttribute($value)
    {
        if (empty($value)) {
            $value = $this->name;
        }

        $this->attributes['slug'] = str_slug($value);
    }

    public function scopeType($query, $type)
    {
        return $query->where('type', $type);
    }
}
