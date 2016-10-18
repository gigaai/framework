<?php


namespace GigaAI\Core\Rule;


class DbRuleRepository implements RuleRepositoryInterface
{

    /**
     * Save a rule
     *
     * @param Rule $rule
     *
     * @return Rule|null
     */
    public function save(Rule $rule)
    {
        if ($rule->save()) {
            return $rule;
        }

        return null;
    }

    /**
     * Return all rules
     *
     * @return \Illuminate\Database\Eloquent\Collection|static[]
     */
    public function getAll()
    {
        return Rule::all();
    }
}