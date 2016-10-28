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

            if (is_callable($answers)) {
                $this->addNode(
                    ['type' => 'callback', 'callback' => $answers],
                    $node_type,
                    $asks
                );

                return $this;
            }

            $answers = (array)$answers;

            // We will keep _wait format.
            // todo: This is no longer exists
            if ( ! empty($answers['_wait']))
                return $this;

            // Short hand method of attachments
            if ($this->isShorthand($answers)) {

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
     * @param Mixed $answer Message
     * @param String $node_type Node Type
     * @param null $asks Question
     *
     * @return Node
     */
    public function addNode($answer, $node_type, $asks = null)
    {
        if (isset($answer['type']) && $answer['type'] === 'callback') {
            $answer['callback'] = $this->serializer->serialize($answer['callback']);
        }

        $this->current_node = Storage::addNode($answer, $node_type, $asks);

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
        if (is_array($answer)) {
            if (
                array_key_exists('_wait', $answer) ||
                (array_key_exists('type', $answer) && $answer['type'] === 'callback') ||
                array_key_exists('attachment', $answer)
            ) {
                return false;
            }
        }

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
     * @return array
     */
    public function parseWithoutSave($answers)
    {
        // Short hand method of attachments
        if ($this->isShorthand($answers))
            return [Parser::parseAnswer($answers)];

        return array_map(['\GigaAI\Core\Parser', 'parseAnswer'], $answers);
    }

    /**
     * Check if answers input is single answer
     *
     * @param $answers
     * @return bool
     */
    private function isShorthand($answers)
    {
        return (
            is_string($answers) ||
            array_key_exists('buttons', $answers) ||
            array_key_exists('elements', $answers) || // For Generic or Receipt
            (is_array($answers[0]) && array_key_exists('title', $answers[0])) || // For Generic
            array_key_exists('text', $answers) || // For Button
            array_key_exists('type', $answers)
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

            $then_node = $this->addNode([
                'type'      => 'callback',
                'callback'  => $action
            ], 'intended', 'IA#' . $related->id);

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