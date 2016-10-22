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
            foreach (array('payload', 'default', 'attachment') as $type) {
                if (strpos($asks, $type . ':') !== false) {
                    $node_type = $type;

                    $asks = ltrim($asks, $type . ':');
                }
            }

            if ($asks[0] == '@') {
                $node_type  = 'intended';
                $asks       = ltrim($asks, '@');
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
            // todo: This is no longer exists
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


    function isParsable($answer)
    {
        if (is_array($answer)) {
            if (
                array_key_exists('_wait', $answer) ||
                (array_key_exists('type', $answer) && $answer['type'] === 'callback') ||
                array_key_exists('attachment', $answer)
            )
                return false;
        }

        return true;
    }

    public function getNodes($type = '', $pattern = '')
    {
        return Node::findByTypeAndPattern($type, $pattern);
    }

    public function parseWithoutSave($answers)
    {
        // Short hand method of attachments
        if ($this->isShorthand($answers))
            return array(Parser::parseAnswer($answers));

        return array_map(['\GigaAI\Core\Parser', 'parseAnswer'], $answers);
    }

    private function isShorthand($answers)
    {
        return (
            is_string($answers) ||
            array_key_exists('buttons', $answers) ||
            array_key_exists('elements', $answers) || // For Generic or Receipt
            (is_array($answers[0]) && array_key_exists('title', $answers[0])) || // For Generic
            array_key_exists('text', $answers) || // For Button
            array_key_exists('type', $answers) ||
            array_key_exists('quick_replies', $answers)
        );
    }

    public function addIntendedAction($action)
    {
        if (empty($this->current_node->type) || $this->current_node->type == 'welcome')
            return;

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

            $this->current_node = $this->current_node->save();
        }
    }
}