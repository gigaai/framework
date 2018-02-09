<?php

namespace GigaAI\Core;

use GigaAI\Message\Button;
use GigaAI\Message\Generic;
use GigaAI\Message\Lists;
use GigaAI\Message\Media;
use GigaAI\Message\Receipt;
use GigaAI\Message\Text;
use GigaAI\Message\Image;
use GigaAI\Message\Audio;
use GigaAI\Message\Video;
use GigaAI\Message\Callback;
use GigaAI\Message\File;
use GigaAI\Storage\Eloquent\Node;
use GigaAI\Storage\Storage;
use SuperClosure\Serializer;
use GigaAI\Message\Raw;
use GigaAI\Message\Command;
use GigaAI\Message\Typing;

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

    /**
     * ! Do not change the priority of these keys
     *
     * @var array
     */
    protected $typeClasses = [
        'image'    => Image::class,
        'video'    => Video::class,
        'audio'    => Audio::class,
        'file'     => File::class,
        'media'    => Media::class,
        'text'     => Text::class,
        'generic'  => Generic::class,
        'button'   => Button::class,
        'list'     => Lists::class,
        'receipt'  => Receipt::class,
        'raw'      => Raw::class,
        'callback' => Callback::class,
        'command'  => Command::class,
        'typing'   => Typing::class,
    ];

    public function __construct()
    {
        // Load serializer to serialize callback
        $this->serializer = new Serializer();
    }

    /**
     * Add Node to the Database
     *
     * @param       $pattern
     * @param null  $answers
     * @param array $attributes
     *
     * @return $this|null
     */
    public function addNode($pattern, $answers = null, $attributes = [])
    {
        // Multiple nodes. If user like to use $patterns => $answers method
        if (is_array($pattern) && is_null($answers)) {
            $this->addNodes($pattern, $attributes);

            return null;
        }

        $type_pattern         = $this->getNodeTypeAndPattern($pattern);
        list($type, $pattern) = $type_pattern;

        if (!is_string($pattern) && is_callable($pattern)) {
            $this->persistNode(
                [['type' => 'callback', 'content' => $answers]],
                $type,
                $pattern,
                $attributes
            );

            return $this;
        }

        if (is_string($answers)) {
            $answers = trim($answers);
        }

        $answers = (array)$answers;
        $answers = $this->parse($answers);

        // Persist to DB
        $this->persistNode($answers, $type, $pattern, $attributes);

        return $this;
    }

    /**
     * Get Node type and Pattern
     *
     * @param $pattern
     *
     * @return array
     */
    private function getNodeTypeAndPattern($pattern)
    {
        $node_type = 'text';

        // If user set payload, default, welcome message.
        foreach (['payload', 'default', 'attachment'] as $type) {
            if (strpos($pattern, $type . ':') !== false) {
                $node_type = $type;
                $pattern   = ltrim($pattern, $node_type . ':');
            }
        }

        if (!empty($pattern) && $pattern[0] == '@') {
            $node_type = 'intended';
            $pattern   = ltrim($pattern, '@');
        }

        return [$node_type, $pattern];
    }

    /**
     * Add Multiple Nodes
     *
     * @param $nodes
     */
    public function addNodes($nodes, $attributes = [])
    {
        foreach ($nodes as $pattern => $answers) {
            $this->addNode($pattern, $answers, $attributes);
        }
    }

    /**
     * Add answer to node
     *
     * @param Mixed  $answers Message
     * @param String $node_type Node Type
     * @param null   $pattern Question
     *
     * @return Node
     */
    public function persistNode(array $answers, $node_type, $pattern = null, $attributes = [])
    {
        foreach ($answers as $index => $answer) {
            if (isset($answer['type']) && isset($answer['content']) && is_callable($answer['content'])) {
                $answer['content'] = $this->serializer->serialize($answer['content']);
            }
            $answers[$index] = $answer;
        }

        $this->current_node = Storage::addNode($answers, $node_type, $pattern, $attributes);

        return $this->current_node;
    }

    /**
     * Check if answer is parsable
     *
     * @param $answer
     *
     * @return bool
     */
    public function isParsable($answer)
    {
        if (is_array($answer) && isset($answer['attachment'])) {
            return false;
        }

        return true;
    }

    /**
     * Get Nodes by type and patterns
     *
     * @param string $type
     * @param string $pattern
     *
     * @return \GigaAI\Storage\Eloquent\Node[]
     */
    public function getNodes($type = '', $pattern = '')
    {
        return Node::findByTypeAndPattern($type, $pattern);
    }

    /**
     * Parse the answers to correct FB Format.
     */
    public function parse($answers)
    {
        // Iterate through answers and parse it if possible
        // Also, move quick replies to the last answer
        $parsed = [];

        $previous_index = 0;

        if ($this->isSingularResponse($answers)) {
            $answers = [$answers];
        }

        foreach ($answers as $index => $answer) {
            // If the answer is a Closure
            if (!is_string($answer) && is_callable($answer)) {
                $answer = [
                    'type'    => 'callback',
                    'content' => $answer,
                ];
            }

            if (isset($answer['content']) && isset($answer['quick_replies'])) {
                $answer['content'] = (array)$answer['content'];

                $answer['content']['quick_replies'] = $answer['quick_replies'];
                unset($answer['quick_replies']);
            }

            // Cast attachment key to other messages
            if (($index === 'attachment' || isset($answer['attachment']))) {
                $answer = $this->parseAttachmentMessage($index, $answer);
            }

            // Parse answer when possible.
            // Iterate through supported message type and return if answer is supported
            if ($this->isParsable($answer) && $index !== 'quick_replies') {
                if (isset($answer['type'])) {
                    $parser       = $this->typeClasses[$answer['type']];

                    $parsedAnswer = $parser::load($answer['content'], [
                        'skip_detection' => true
                    ]);

                    if ($parsedAnswer !== false) {
                        $answer = $parsedAnswer;
                    }
                } else {
                    foreach ($this->typeClasses as $type => $parser) {
                        // If not supported, it will return false, otherwise, return parsed data
                        $parsedAnswer = $parser::load($answer);

                        if ($parsedAnswer !== false) {
                            $answer = $parsedAnswer;

                            break;
                        }
                    }
                }
            }

            if ($index === 'quick_replies') {
                $parsed[$previous_index]                             = (array)$parsed[$previous_index];
                $parsed[$previous_index]['content']['quick_replies'] = $answer;
            }

            $parsed[$index] = $answer;
            $previous_index = $index;
        }

        unset($parsed['quick_replies']);

        return $parsed;
    }

    /**
     * If message starts with `attachment` parameter. We'll keep everything inside that param and
     * only check message type.
     *
     * @return array
     */
    private function parseAttachmentMessage($index, $answer)
    {
        $templateType = null;

        if (isset($answer['type'])) {
            $templateType = $answer['type'];
        }

        $attachment = $index === 'attachment' ? $answer : $answer['attachment'];

        if (isset($attachment['payload']) && isset($attachment['payload']['template_type'])) {
            $templateType = $attachment['payload']['template_type'];
        }

        return [
            'type'    => $templateType,
            'content' => $answer
        ];
    }

    /**
     * Check if answers input is single answer
     *
     * @param $answer
     *
     * @return bool
     */
    private function isSingularResponse($answer)
    {
        return (
            is_string($answer) ||
            is_callable($answer) ||
            array_key_exists('buttons', $answer) ||
            array_key_exists('elements', $answer) || // For Generic or Receipt
            (is_array($answer) && isset($answer[0]) && is_array($answer[0]) && array_key_exists(
                'title',
                    $answer[0]
            )) || // For Generic
            isset($answer['text']) || // For Button
            isset($answer['type']) || // For type => content =>
            isset($answer['attachment']) // For attachment
        );
    }

    /**
     * Add intended action for current node
     *
     * @param $action
     */
    public function addIntendedAction($action)
    {
        if (empty($this->current_node->type) || $this->current_node->type == 'welcome') {
            return;
        }

        // If it's ->then() intended action. We'll save next action as id
        if (is_callable($action)) {
            $related = $this->current_node;

            $then_node = $this->persistNode([
                [
                    'type'    => 'callback',
                    'content' => $action,
                ],
            ], 'intended', 'IA#' . $related->id);

            $related->wait = $then_node->id;

            $related->save();
        } else {
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
        if (empty($this->current_node)) {
            return;
        }

        $this->current_node->tags = $tag;
        $this->current_node->save();
    }
}
