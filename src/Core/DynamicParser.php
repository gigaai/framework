<?php

namespace GigaAI\Core;

use GigaAI\Shared\EasyCall;
use GigaAI\Shared\Singleton;

class DynamicParser
{
    use Singleton, EasyCall;

    public $supports = [];

    private function support($type = [])
    {
        if (empty($type['type']) || !is_callable($type['callback'])) {
            throw new \Exception('Your parser is wrong format!');
        }

        $this->supports[$type['type']] = $type['callback'];
    }

    private function parse($answer = [])
    {
        if (!array_key_exists('type', $answer) || !array_key_exists('content', $answer)) {
            throw new \Exception('Invalid num of required fields!');
        }

        if (!array_key_exists($answer['type'], $this->supports) || !is_callable($this->supports[$answer['type']])) {
            throw new \Exception('Parser input is not valid');
        }

        $parser = $this->supports[$answer['type']];

        return @call_user_func_array($parser, [$answer['content']]);
    }
}
