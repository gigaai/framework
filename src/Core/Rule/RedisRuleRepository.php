<?php


namespace GigaAI\Core\Rule;

class RedisRuleRepository implements RuleRepositoryInterface
{
    private $redis;

    public function __construct($redis)
    {
        $this->redis = $redis;
    }

    public function save(Rule $rule)
    {
        if (empty($rule->id)) {
            $rule->id = time();
        }

        $key = "rules";
        $rules = unserialize($this->redis->get($key));
        if (empty($rules)) {
            $rules = [];
        }

        $rules[] = $rule;

        $this->redis->set($key, serialize($rules));
    }

    public function getAll()
    {
        $key = "rules";
        return unserialize($this->redis->get($key));
    }
}