<?php

namespace GigaAI\Storage\Eloquent;

use GigaAI\Conversation\Conversation;

class Node extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_nodes';

    protected $fillable = [
        'source',
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
        'meta' => 'json',
        'tags' => 'json',
    ];

    public function creator()
    {
        return $this->belongsTo('App\User', 'creator_id', 'id');
    }

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

        if ( ! empty($type)) {
            $where_type           = ' AND type = :type';
            $placeholder[':type'] = $type;
        }

        if ( ! empty($pattern)) {
            $placeholder[':pattern'] = $pattern;
            $where_like              = " AND :pattern LIKE pattern";
            $where_rlike             = " AND :pattern RLIKE CONCAT('^',pattern,'$')";
        }

        $columns = ['type', 'pattern', 'answers', 'wait', 'source'];

        // Where Like First
        $nodes = self::whereRaw($where . $where_type . $where_like, $placeholder)->get($columns);
        // If Not Found. Then Where Rlike
        if ($nodes->count() === 0) {
            $nodes = self::whereRaw($where . $where_type . $where_rlike, $placeholder)->get($columns);
        }

        // If still not found, then try NLP and find
        if ($nodes->count() === 0) {
            $entities = Conversation::get('nlp')->getNames();

            if ( ! empty($entities)) {
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
        if ( ! empty($value)) {
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
        if ( ! empty($value)) {
            return $query->where('pattern', 'like', '%' . $value . '%')
                         ->orWhere('answers', 'like', '%' . $value . '%');
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