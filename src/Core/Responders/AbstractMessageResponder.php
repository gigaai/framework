<?php


namespace GigaAI\Core\Responders;


use GigaAI\Core\RuleManager;


/**
 * Class AbstractMessageResponder
 * @package GigaAI\Core\Responders
 */
abstract class AbstractMessageResponder implements MessageResponderInterface
{
    protected $rules = [];

    /**
     * AbstractMessageResponder constructor.
     *
     * @param RuleManager $ruleManager
     */
    public function __construct(RuleManager $ruleManager)
    {
        $this->rules = $ruleManager->getAll();
    }
}