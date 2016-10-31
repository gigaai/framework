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

    /**
     * Parse $bot->answer() method to extract the answers
     *
     * @param $asks
     * @param null $answers
     * @return $this
     */
    public function parseAnswers($asks, $answers = null)
    {
        if (is_string($asks)) {
            $node_type = 'text';

            // If user set payload, default, welcome message.
            foreach (['payload', 'default', 'attachment'] as $type) {
                if (strpos($asks, $type . ':') !== false) {
                    $node_type = $type;

                    $asks = ltrim($asks, $type . ':');
                }
            }

            if ( ! empty($asks) && $asks[0] == '@') {
                $node_type  = 'intended';
                $asks       = ltrim($asks, '@');
            }

            if (is_string($answers)) {
                $answers = trim($answers);
            }

            if (is_callable($answers)) {

                $this->addNode(
                    [['type' => 'callback', 'content' => $answers]],
                    $node_type,
                    $asks
                );

                return $this;
            }

            $answers = (array)$answers;

            // Short hand method of attachments
            if ($this->isSingleAnswer($answers)) {

                if ($this->isParsable($answers)) {
                    $answers = Parser::parseAnswer($answers);
                }

                $this->addNode([$answers], $node_type, $asks);

                return $this;
            }

            // Iterate through answers and parse it if possible
            // Also, move quick replies to the last answer

            $parsed = [];

            $previous_index = 0;

            foreach ($answers as $index => $answer) {

                if (is_callable($answer)) {
                    $answer = [
                        'type' => 'callback',
                        'content' => $answer
                    ];
                }

                if ($this->isParsable($answer) && $index !== 'quick_replies') {
                    $answer = Parser::parseAnswer($answer);
                }

                if ($index === 'quick_replies') {
                    $parsed[$previous_index] = (array)$parsed[$previous_index];
                    $parsed[$previous_index]['quick_replies'] = $answer;
                }

                $parsed[$index] = $answer;
                $previous_index = $index;
            }

            unset($parsed['quick_replies']);

            $this->addNode($parsed, $node_type, $asks);
        }

        // Recursive if we set multiple asks, responses
        if (is_array($asks) && is_null($answers)) {
            if (array_key_exists('text', $asks) || array_key_exists('payload', $asks) || array_key_exists('attachment', $asks)) {
                foreach ($asks as $event => $nodes) {

                    $prepend = $event === 'text' ? '' : $event . ':';

                    if ($event === 'default')
                        $nodes = [$nodes];

                    foreach ($nodes as $ask => $responses) {
                        $this->parseAnswers($prepend . $ask, $responses);
                    }
                }
            }
            else {
                foreach ($asks as $ask => $answers) {
                    $this->parseAnswers($ask, $answers);
                }
            }
        }

        return $this;
    }

    /**
     * Add answer to node
     *
     * @param Mixed $answers Message
     * @param String $node_type Node Type
     * @param null $asks Question
     *
     * @return Node
     */
    public function addNode(array $answers, $node_type, $asks = null)
    {
        foreach ($answers as $index => $answer) {
            if (isset($answer['type']) && isset($answer['content']) && is_callable($answer['content'])) {
                $answer['content'] = $this->serializer->serialize($answer['content']);
            }
            $answers[$index] = $answer;
        }

        $this->current_node = Storage::addNode($answers, $node_type, $asks);

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
     * Parse [a] answers without save
     *
     * @param $answers
     * @return mixed
     */
    public function parseWithoutSave($answers)
    {
        if ( ! $this->isParsable($answers)) {
            return false;
        }

        // Short hand method of attachments
        if ($this->isSingleAnswer($answers))
            return [Parser::parseAnswer($answers)];

        return array_map(['\GigaAI\Core\Parser', 'parseAnswer'], $answers);
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

            $then_node = $this->addNode([[
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