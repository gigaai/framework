<?php

namespace GigaAI\Core;

use GigaAI\Storage\Storage;

class Model
{
    public $answers = array(
        'text' => array(),
        'payload' => array(),
        'default' => array(),
        'attachment' => array()
    );

    public $current_node = array();

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
                $this->addAnswer(
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

                $this->addAnswer(array($answers), $node_type, $asks);

                return $this;
            }

            $parsed = array();

            foreach ($answers as $answer) {
                if ($this->isParsable($answer))
                    $parsed[] = Parser::parseAnswer($answer);
                else
                    $parsed[] = $answer;
            }

            $this->addAnswer($parsed, $node_type, $asks);
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
    public function addAnswer($answer, $node_type, $asks = null)
    {
        $this->current_node = compact('node_type', 'asks');

        $storage_driver = Config::get('storage_driver', 'file');

        if ($storage_driver === 'file' || (isset($answer['type']) && $answer['type'] === 'callback')) {

            if (isset($answer['type']) && $answer['type'] === 'callback') {
                Storage::removeAnswers($node_type, $asks);
                $answer = array($answer);
            }

            if (in_array($node_type, array('text', 'payload', 'attachment'))) {

                if ( ! isset($this->answers[$node_type][$asks]))
                    $this->answers[$node_type][$asks] = array();

                $this->answers[$node_type][$asks] = $answer;
            }

            if ($node_type === 'default')
                $this->answers[$node_type] = $answer;
        } else {
            Storage::addAnswer($answer, $node_type, $asks);
        }
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
        // Check in storage driver
        $search_in_storage = array();

        $storage_driver = Config::get('storage_driver', 'file');

        if ($storage_driver != 'file')
            $search_in_storage = Storage::getAnswers($node_type, $ask);

        $answers = array_merge_recursive($search_in_storage, $this->answers);

        if ( ! empty($node_type) && ! empty($answers[$node_type]))
            $answers = $answers[$node_type];

        if ( ! empty($node_type) && ! empty($ask) && ! empty($answers[$ask]))
            $answers = $answers[$ask];

        // Get intended action in `text` node.
        // Todo: Arrange to get in all node
        if ( (empty($node_type) || is_null($node_type)) &&
            ! empty($ask) && $ask[0] === '@' &&
            isset($answers['text'][$ask])
        )
            $answers = $answers['text'][$ask];

        return $answers;
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

    public function addIntendedAction($action, $message_type = '')
    {
        if (empty($this->current_node['node_type']) || $this->current_node['node_type'] == 'welcome')
            return;

        $answers = $this->getAnswers($this->current_node['node_type'], $this->current_node['asks']);
        $answers[] = array('_wait' => $action);

        $this->addAnswer(
            $answers,
            $this->current_node['node_type'],
            $this->current_node['asks']
        );
    }
}