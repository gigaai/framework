<?php


namespace GigaAI\Core\Rule;
use SuperClosure\Serializer;


/**
 * Class RuleManager
 *
 * @package GigaAI\Core
 */
class RuleManager
{
    /**
     * @var array Rule
     */
    private $rules = [];

    /**
     * @var Rule
     */
    private $currentRule;

    private $ruleRepository;

    public function __construct(RuleRepositoryInterface $ruleRepository)
    {
        $this->ruleRepository = $ruleRepository;
    }

    public function initialized()
    {
        $rules = $this->ruleRepository->getAll();

        return !empty($rules);
    }

    public function addRule($request, $response)
    {
        $rule = new Rule($request, $response);
        $this->currentRule = $rule;

        $this->ruleRepository->save($rule);
    }

    public function addThenHandler(callable $callback)
    {
        if (!$this->currentRule) {
            return;
        }

        $serializer = new Serializer();
        $serializedCallback = $serializer->serialize($callback);
        $this->currentRule->thenHandler = $serializedCallback;

        $this->ruleRepository->save($this->currentRule);
    }

    public function getAll()
    {
        return $this->ruleRepository->getAll();
    }
}