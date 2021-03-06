<?php

namespace GigaAI\Storage\Eloquent;

use GigaAI\Conversation\Conversation;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

class Node extends Model
{
    use ForOwner, SoftDeletes, HasCreator;

    public $table = 'giga_nodes';

    protected $fillable = [
        'sources',
        'creator_id',
        'pattern',
        'answers',
        'wait',
        'type',
        'notification_type',
        'status',
        'tags',
        'meta',
    ];

    protected $casts = [
        'meta'    => 'json',
        'tags'    => 'json',
        'sources' => 'json'
    ];

    /**
     * Get node by node type and pattern
     *
     * @param $type
     * @param $pattern
     *
     * @return Node[]
     */
    public static function findByTypeAndPattern($type = '', $pattern = '')
    {
        $where       = '1 = 1';
        $where_type  = '';
        $where_like  = '';
        $where_rlike = '';

        $placeholder = [];

        // Check type, pattern is email or phone number when passing from quick replies
        if ($type === 'payload') {
            $pattern = filter_var($pattern, FILTER_VALIDATE_EMAIL) ? 'user_email' : $pattern;
            $pattern = is_phone_number($pattern) ? 'user_phone_number' : $pattern;
        }

        if (!empty($type)) {
            $where_type           = ' AND type = :type';
            $placeholder[':type'] = $type;
        }

        if (!empty($pattern)) {
            $placeholder[':pattern'] = $pattern;
            $where_like              = ' AND :pattern LIKE pattern';
            $where_rlike             = " AND :pattern RLIKE CONCAT('^',pattern,'$')";
        }

        $columns = ['type', 'pattern', 'answers', 'wait', 'sources', 'messaging_type', 'notification_type'];

        // Where Like First
        $nodes = self::whereRaw($where . $where_type . $where_like, $placeholder)->get($columns);
        
        // If Not Found. Then Where Rlike
        if ($nodes->count() === 0) {
            $nodes = self::whereRaw($where . $where_type . $where_rlike, $placeholder)->get($columns);
        }

        // If still not found, then try NLP and find
        if ($nodes->count() === 0 && Conversation::has('nlp')) {
            $entities = Conversation::get('nlp')->getNames();

            if (! empty($entities)) {
                $entities = '#' . ltrim(implode('|#', $entities), '|');
                
                $nodes = self::whereRaw('pattern RLIKE :pattern', [
                    ':pattern' => $entities,
                ])->get($columns);

                // Enable nodes filter. Available types: first, last, any. Default: first
                $nodes = $nodes->filter(function ($node) {
                    return Conversation::get('nlp')->filter($node->pattern)->exists();
                });
            }
        }

        return $nodes;
    }

    /**
     * Query scope tag
     *
     * @param $query
     * @param $value
     *
     * @return mixed
     */
    public function scopeOfTag($query, $value)
    {
        if (!empty($value)) {
            return $query->where('tags', 'like', '%' . $value . '%');
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
            $value = '%' . implode(explode(' ', $value), '%') . '%';

            return $query->where('pattern', 'LIKE', $value)
                          ->orWhere('answers', 'LIKE', $value)
                          ->orWhere('tags', 'LIKE', $value);
        }

        return $query;
    }

    /**
     * Query Scope
     *
     * @param $query
     *
     * @return mixed
     */
    public function scopeNotFluentIntended($query)
    {
        $query->where('pattern', 'not like', 'IA#%');

        return $query;
    }

    public function scopeWithGlobal($query)
    {
        $query->where('sources', '')->orWhereNull('sources')->orWhere('sources', 'LIKE', '"global":true');

        return $query;
    }

    /**
     * Auto json encode the answers attribute
     *
     * @param $value
     */
    public function setAnswersAttribute($value)
    {
        if (is_array($value)) {
            $value                       = giga_array_filter($value);
            $this->attributes['answers'] = json_encode($value, JSON_UNESCAPED_UNICODE);
        } else {
            $this->attributes['answers'] = $value;
        }
    }

    /**
     * Auto json decode the answer attribute
     *
     * @param $value
     *
     * @return mixed
     */
    public function getAnswersAttribute($value)
    {
        return json_decode($value, true);
    }
}
