<?php


namespace GigaAI\Core\Rule;


class DbRuleRepository implements RuleRepositoryInterface
{

    public function save(Rule $rule)
    {
        return $rule;
    }

    public function getAll()
    {
        return [];
    }
}