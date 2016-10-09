<?php


namespace GigaAI\Core\Responders;
use GigaAI\Core\Rule\Rule;

/**
 * Class DefaultMessageResponder
 *
 * @package GigaAI\Core\Responders
 */
class DefaultMessageResponder extends AbstractMessageResponder
{
    /**
     * @inheritdoc
     */
    protected function getMatchedRule($input)
    {
        $matchedRule = null;

        foreach ($this->rules as $rule) {
            /** @var Rule $rule */
            if ($this->match($rule->request, $input)) {
                $matchedRule = $rule;
                break;
            }
        }

        if (!$matchedRule) {
            $defaultRule = array_filter($this->rules, function($rule) {
                /** @var Rule $rule */
                return $rule->request === 'default:';
            });

            if ($defaultRule) {
                $matchedRule = reset($defaultRule);
            }
        }

        return $matchedRule;
    }

    /**
     * @inheritdoc
     */
    protected function match($pattern, $string)
    {
        if (strpos($pattern, 'regex:') !== false)
        {
            $pattern = str_replace('regex:', '', $pattern);

            return preg_match($pattern, $string);
        }

        $pattern = strtr($pattern, array(
            '%' => '[\s\S]*',
            '?' => '\?',
            '*' => '\*',
            '+' => '\+',
            '.' => '\.'
        ));

        return preg_match("/^$pattern$/i", $string);
    }
}