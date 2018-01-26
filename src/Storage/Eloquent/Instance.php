<?php

namespace GigaAI\Storage\Eloquent;

use GigaAI\Conversation\Conversation;
use GigaAI\Core\Config;
use Illuminate\Database\Eloquent\Model;

class Instance extends Model
{
    public $table = 'giga_instances';

    protected $fillable = [
        'id',
        'name',
        'user_id',
        'app_id',
        'app_secret',
        'page_id',
        'access_token',
        'photo',
        'meta',
        'status',
    ];

    public $timestamps = false;

    public $casts = [
        'id'   => 'string',
        'meta' => 'json',
    ];


    public function getMetaAttribute($value)
    {
        return json_decode($value, true);
    }

    public static function get($key, $default = null, $page_id = null)
    {
        if (is_null($page_id)) {
            $page_id = Conversation::get('page_id');
        }
        if (isset($_GET['page_id'])) {
            $page_id = trim($_GET['page_id']);
        }

        if (is_null($page_id)) {
            return Config::get($key, $default);
        }

        $instance = self::wherePageId($page_id)->first();

        if (isset($instance->$key)) {
            return $instance->$key;
        }

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

        $instance             = self::find($page_id);
        $instance->meta[$key] = $value;
        $instance->save();
    }

    public function getMeta($key, $default = null)
    {
        $meta = $this->meta;

        if (isset($meta[$key])) {
            return $meta[$key];
        }

        return $default;
    }

    public function nodes()
    {
        return $this->hasMany(Node::class, 'sources', 'page_id');
    }

    public function leads()
    {
        return $this->hasMany(Lead::class, 'source', 'page_id');
    }

    public function broadcasts()
    {
        return $this->hasMany(Broadcast::class);
    }

    public function getPhotoAttribute($value)
    {
        if (!empty($value)) {
            return 'data:image/png;base64,' . base64_encode($value);
        }

        return asset('/img/pages-flag.png');
    }
}