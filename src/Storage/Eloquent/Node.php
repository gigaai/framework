<?php

namespace GigaAI\Storage\Eloquent;

class Node extends \Illuminate\Database\Eloquent\Model
{
    public $table = 'bot_nodes';

    protected $fillable = ['instance_id', 'pattern', 'answers', 'wait', 'sources', 'type', 'notification_type', 'status', 'tags'];

    protected $casts = [
        'answers' => 'array'
    ];

    /**
     * Get node by node type and pattern
     *
     * @param $type
     * @param $pattern
     * @return Node[]
     */
    public static function findByTypeAndPattern($type = '', $pattern = '')
    {
        $where          = '1 = 1';
        $placeholder    = [];

        if ( ! empty($type)) {
            $where                  .= ' AND type = :type';
            $placeholder[':type']   = $type;
        }

        if ( ! empty($pattern)) {
            $placeholder[':pattern'] = $pattern;

            // Intended Action. We'll get first row.
            if ($pattern[0] === '@') {
                $where .= ' AND pattern = :pattern';
            }
            else {
                $where .= " AND (:pattern RLIKE pattern OR :pattern2 LIKE pattern)";
                $placeholder[':pattern2'] = $pattern;
            }
        }

        $nodes = Node::whereRaw($where, $placeholder)->get(['type', 'pattern', 'answers', 'wait']);

        foreach ($nodes as $index => $node)
        {
            if ( ! giga_match($node->pattern, $pattern))
                unset($nodes[$index]);
        }

        return $nodes;
    }

    public function scopeOfTag($query, $value)
    {
        if ( ! empty($value))
            return $query->where('tags', 'like', '%' . $value . '%');

        return $query;
    }

    public function scopeSearch($query, $value)
    {
        if ( ! empty($value))
            return $query->where('pattern', 'like', '%' . $value . '%')
                        ->orWhere('answers', 'like', '%' . $value . '%');

        return $query;
    }

    public function scopeNotFluentIntended($query)
    {
        $query->where('pattern', 'not like', 'IA#%');

        return $query;
    }
}