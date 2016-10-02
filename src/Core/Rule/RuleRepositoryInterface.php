<?php


namespace GigaAI\Core\Rule;


interface RuleRepositoryInterface
{
    public function save(Rule $rule);

    public function getAll();
}