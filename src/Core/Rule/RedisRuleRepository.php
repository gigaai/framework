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
            $rule->id = rand(1, 1000000000);
        }

        $key = "rules";
        $rules = unserialize($this->redis->get($key));
        if (empty($rules)) {
            $rules = [];
        }

        $hasExistingRule = false;
        foreach ($rules as &$existingRule) {
            if ($existingRule->id === $rule->id) {

                // Update existing rule
                $existingRule = $rule;
                break;
            }
        }

        if (!$hasExistingRule) {
            // Add new rule
            $rules[] = $rule;
        }

        $this->redis->set($key, serialize($rules));
    }

    public function getAll()
    {
        $key = "rules";
        return unserialize($this->redis->get($key));
    }
}