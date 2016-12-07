<?php

namespace GigaAI\Core;

use GigaAI\Storage\Eloquent\Node;
use GigaAI\Storage\Storage;
use SuperClosure\Serializer;

class Model
{
    /**
     * @var Serializer
     */
    private $serializer;

    /**
     * @var Node
     */
    public $current_node;

    /**
     * @var Node[]
     */
    public $nodes;

    public function __construct()
    {
        // Load serializer to serialize callback
        $this->serializer = new Serializer();
    }

    public function addNode($pattern, $answers = null)
    {
        // If user like to use $pattern => $answers method
        if (is_array($pattern) && is_null($answers)) {
            $this->addNodes($pattern);

            return null;
        }

        $type_pattern = $this->getNodeTypeAndPattern($pattern);
        list($type, $pattern) = $type_pattern;

        if (is_callable($pattern)) {

            $this->persistNode(
                [['type' => 'callback', 'content' => $answers]],
                $type,
                $pattern
            );

            return $this;
        }

        if (is_string($answers)) {
            $answers = trim($answers);
        }

        $answers = (array) $answers;
        $answers = $this->parseAnswers($answers);

        $this->persistNode($answers, $type, $pattern);

        return $this;
    }

    private function getNodeTypeAndPattern($pattern)
    {
        $node_type = 'text';

        // If user set payload, default, welcome message.
        foreach (['payload', 'default', 'attachment'] as $type) {
            if (strpos($pattern, $type . ':') !== false) {
                $node_type = $type;
                $pattern = ltrim($pattern, $node_type . ':');
            }
        }

        if ( ! empty($pattern) && $pattern[0] == '@') {
            $node_type  = 'intended';
            $pattern    = ltrim($pattern, '@');
        }

        return [$node_type, $pattern];
    }

    public function addNodes($nodes)
    {
        foreach ($nodes as $pattern => $answers) {
            $this->addNode($pattern, $answers);
        }
    }

    /**
     * Add answer to node
     *
     * @param Mixed $answers Message
     * @param String $node_type Node Type
     * @param null $pattern Question
     *
     * @return Node
     */
    public function persistNode(array $answers, $node_type, $pattern = null)
    {
        foreach ($answers as $index => $answer) {
            if (isset($answer['type']) && isset($answer['content']) && is_callable($answer['content'])) {
                $answer['content'] = $this->serializer->serialize($answer['content']);
            }
            $answers[$index] = $answer;
        }

        $this->current_node = Storage::addNode($answers, $node_type, $pattern);

        return $this->current_node;
    }

    /**
     * Check if current answer is parsable
     *
     * @param $answer
     * @return bool
     */
    public function isParsable($answer)
    {
        if (is_array($answer) && (array_key_exists('attachment', $answer) || array_key_exists('type', $answer)))
            return false;

        return true;
    }

    /**
     * Get Nodes by type and patterns
     *
     * @param string $type
     * @param string $pattern
     * @return \GigaAI\Storage\Eloquent\Node[]
     */
    public function getNodes($type = '', $pattern = '')
    {
        return Node::findByTypeAndPattern($type, $pattern);
    }

    /**
     * Parse Quick Replies
     */
    private function parseAnswers($answers)
    {
        // Iterate through answers and parse it if possible
        // Also, move quick replies to the last answer
        $parsed = [];

        $previous_index = 0;

        if ($this->isSingleAnswer($answers)) {
            $answers = [$answers];
        }

        foreach ($answers as $index => $answer) {

            if (is_callable($answer)) {
                $answer = [
                    'type' => 'callback',
                    'content' => $answer
                ];
            }

            if ($this->isParsable($answer) && $index !== 'quick_replies') {

                $message_types = ['Media', 'Text', 'Generic', 'Button', 'Receipt'];

                foreach ($message_types as $type) {
                    $parsed_answer = call_user_func_array(["\\GigaAI\\Message\\$type", 'load'], [$answer]);

                    if ($parsed_answer !== false) {
                        $answer = $parsed_answer;
                    }
                }
            }

            if ($index === 'quick_replies') {
                $parsed[$previous_index] = (array)$parsed[$previous_index];
                $parsed[$previous_index]['quick_replies'] = $answer;
            }

            $parsed[$index] = $answer;
            $previous_index = $index;
        }

        unset($parsed['quick_replies']);
        return $parsed;
    }

    /**
     * Parse [a] answers without save
     *
     * @param $answers
     * @return mixed
     */
    public function parseWithoutSave($answers)
    {
        return $this->parseAnswers($answers);
    }

    /**
     * Check if answers input is single answer
     *
     * @param $answer
     * @return bool
     */
    private function isSingleAnswer($answer)
    {
        return (
            is_string($answer) ||
            is_callable($answer) ||
            array_key_exists('buttons', $answer) ||
            array_key_exists('elements', $answer) || // For Generic or Receipt
            (is_array($answer[0]) && array_key_exists('title', $answer[0])) || // For Generic
            array_key_exists('text', $answer) || // For Button
            array_key_exists('type', $answer)
        );
    }

    /**
     * Add intended action for current node
     *
     * @param $action
     */
    public function addIntendedAction($action)
    {
        if (empty($this->current_node->type) || $this->current_node->type == 'welcome')
            return;

        // If it's ->then() intended action. We'll save next action as id
        if (is_callable($action))
        {
            $related = $this->current_node;

            $then_node = $this->persistNode([[
                'type'      => 'callback',
                'content'   => $action
            ]], 'intended', 'IA#' . $related->id);

            $related->wait = $then_node->id;

            $related->save();
        }
        else {
            $this->current_node->wait = $action;

            $this->current_node->save();
        }
    }

    /**
     * Tag a node
     *
     * @param $tag
     */
    public function taggedAs($tag)
    {
        if (empty($this->current_node))
            return;

        $this->current_node->tags = $tag;
        $this->current_node->save();
    }
}