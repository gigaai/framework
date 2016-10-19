<?php

namespace GigaAI\Core;

use GigaAI\Storage\Eloquent\Node;
use GigaAI\Storage\Storage;
use SuperClosure\Serializer;
use SuperClosure\Analyzer\TokenAnalyzer;

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
        $this->serializer = new Serializer;
    }

    public function parseAnswers($asks, $answers = null)
    {
        if (is_string($asks)) {
            $node_type = 'text';

            // If user set payload, default, welcome message.
            foreach (array('payload', 'default', 'attachment') as $type) {
                if (strpos($asks, $type . ':') !== false) {
                    $node_type = $type;

                    $asks = ltrim($asks, $type . ':');
                }
            }

            if (is_callable($answers)) {
                $this->addNode(
                    array('type' => 'callback', 'callback' => $answers),
                    $node_type,
                    $asks
                );

                return $this;
            }

            $answers = (array)$answers;

            // We will keep _wait format.
            if ( ! empty($answers['_wait']))
                return $this;

            // Short hand method of attachments
            if ($this->isShorthand($answers)) {
                if ($this->isParsable($answers))
                    $answers = Parser::parseAnswer($answers);

                $this->addNode(array($answers), $node_type, $asks);

                return $this;
            }

            $parsed = array();

            foreach ($answers as $answer) {
                if ($this->isParsable($answer))
                    $parsed[] = Parser::parseAnswer($answer);
                else
                    $parsed[] = $answer;
            }

            $this->addNode($parsed, $node_type, $asks);
        }

        // Recursive if we set multiple asks, responses
        if (is_array($asks) && is_null($answers)) {
            if (array_key_exists('text', $asks) || array_key_exists('payload', $asks) || array_key_exists('attachment', $asks)) {
                foreach ($asks as $event => $nodes) {
                    $prepend = $event === 'text' ? '' : $event . ':';
                    if ($event === 'default')
                        $nodes = array($nodes);

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
     */
    public function addNode($answer, $node_type, $asks = null)
    {
        if (isset($answer['type']) && $answer['type'] === 'callback') {
            $answer['callback'] = $this->serializer->serialize($answer['callback']);
        }

        $this->current_node = Storage::addNode($answer, $node_type, $asks);

        return $this->current_node;
    }


    function isParsable($answer)
    {
        if (is_string($answer))
            return true;

        if (isset($answer['_wait']))
            return false;

        if (isset($answer['type']) && $answer['type'] === 'callback')
            return false;

        if (isset($answer['attachment']))
            return false;

        return true;
    }

    public function getAnswers($node_type = '', $ask = '')
    {
        return Storage::getNodes($node_type, $ask);
    }

    public function addReply($answers)
    {
        // Short hand method of attachments
        if ($this->isShorthand($answers))
            return array(Parser::parseAnswer($answers));

        return array_map(array('\GigaAI\Core\Parser', 'parseAnswer'), $answers);
    }

    private function isShorthand($answers)
    {
        return (
            array_key_exists('buttons', $answers) ||
            array_key_exists('elements', $answers) || // For Generic or Receipt
            (is_array($answers[0]) && array_key_exists('title', $answers[0])) || // For Generic
            array_key_exists('text', $answers) || // For Button
            array_key_exists('type', $answers) ||
            array_key_exists('quick_replies', $answers) ||
            is_string($answers)
        );
    }

    public function addIntendedAction($action)
    {
        if (empty($this->current_node->type) || $this->current_node->type == 'welcome')
            return;

        /** @todo  Support old syntax _wait => action */
        $this->current_node->wait = $action;

        $this->current_node = $this->current_node->save();
    }

    public function addThenAction(callable $callback)
    {
        if (empty($this->current_node->type) || $this->current_node->type == 'welcome')
            return;

        $related = $this->current_node;

        $then_node = $this->addNode([
            'type'      => 'callback',
            'callback'  => $callback
        ], 'intended', 'IA#' . $related->id);

        $related->wait = $then_node->id;

        $related->save();
    }

    /**
     * Todo: This method should returns array of answers
     * @param $action
     * @return \Illuminate\Support\Collection|null|static
     */
    public function getIntendedAction($action)
    {
        if (is_numeric($action)) {
            return Node::find($action);
        }
    }
}