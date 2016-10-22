<?php

namespace GigaAI;

use GigaAI\Storage\Storage;
use GigaAI\Http\Request;
use GigaAI\Conversation\Conversation;
use GigaAI\Core\Model;
use GigaAI\Core\Config;
use SuperClosure\Serializer;
use GigaAI\Storage\Eloquent\Node;
use GigaAI\Storage\Eloquent\Lead;

class MessengerBot
{
    public $request;

    public $storage;

    private $model;

    public $config;

    private $serializer;

    public $conversation;

    public $received;

    private $message;
    /**
     * Load the required resources
     *
     * @param array $config
     */
    public function __construct(array $config = [])
    {
        // Extension version
        if ( ! defined('GIGAAI_VERSION'))
            define('GIGAAI_VERSION', '1.2');

        // Setup the configuration data
        $this->config = Config::getInstance();
        if ( ! empty($config))
            $this->config->set($config);

        // Make a Request instance. Not required but it will help user use $bot->request syntax
        $this->request  = Request::getInstance();

        // Make a Session instance. Not required but it will help user use $bot->session syntax
        $this->conversation = Conversation::getInstance();

        // Load the storage
        $this->storage  = new Storage;

        // Load the model
        $this->model    = new Model;

        $this->serializer = new Serializer();
    }

    public function answer($ask, $answers = null)
    {
        return $this->answers($ask, $answers);
    }

    /**
     * Format answer from short hand to proper form.
     *
     * @param $asks
     * @param null $answers
     *
     * @return $this For chaining method
     */
    public function answers($asks, $answers = null)
    {
        $this->model->parseAnswers($asks, $answers);

        return $this;
    }

    public function run()
    {
        $received = $this->request->getReceivedData();

        if ( ! $received || empty($received->object) || $received->object != 'page')
            return;

        $this->received = $received;

        foreach ($received->entry as $entry)
        {
            foreach ($entry->messaging as $event)
            {
                $this->conversation->set([
                    'sender_id'    => $event->sender->id,
                    'recipient_id' => $event->recipient->id,
                    'timestamp'    => $event->timestamp
                ]);

                $this->processEvent($event);
            }
        }
    }

    public function processEvent($event)
    {
        // Currently, we only handle message and postback
        if ( ! isset($event->message) && ! isset($event->postback))
            return;

        if (isset($event->message)) {
            $this->message = $event->message;

            // If current message is send from Lead
            if ( ! isset($event->message->metadata) || $event->message->metadata != 'SENT_BY_GIGA_AI') {

                if ( ! $this->conversation->has('lead_id')) {
                    $this->conversation->set('lead_id', $event->sender->id);

                    // Save lead data if not exists.
                    $this->storage->pull($event->sender->id);
                }
            }
        }

        $type_pattern = $this->request->getTypeAndPattern($event);

        // We'll check to response intended action first
        if ($this->responseIntendedAction())
            return;

        $nodes = $this->findNodes($type_pattern['type'], $type_pattern['pattern']);

        $this->response($nodes);
    }


    /**
     * Response sender message
     *
     * @param $nodes
     * @param null $lead_id
     */
    public function response($nodes, $lead_id = null)
    {
        if (is_null($lead_id))
            $lead_id = $this->conversation->get('lead_id');

        foreach ($nodes as $node) {

            /** New wait */
            if ( ! empty($node->wait)) {
                $this->storage->set($lead_id, '_wait', $node->wait);
            }

            /** Process callback */
            if (! empty($node->answers) && isset($node->answers['type']) && $node->answers['type'] === 'callback') {

                $callback = $this->serializer->unserialize($node->answers['callback']);

                if (is_callable($callback)) {
                    $return = @call_user_func_array($callback, [$this, $this->getLeadId(), $this->getReceivedText()]);

                    // If the callback return, we'll send that message to user.
                    if ($return != null || ! empty($return))
                    {
                        $return = $this->model->parseWithoutSave($return);

                        $this->request->sendMessages($return);
                    }
                }

                continue;
            }

            $this->request->sendMessages($node->answers);
        }
    }

    /**
     * Alias of says() method
     *
     * @param $messages
     * @return $this
     */
    public function say($messages)
    {
        return $this->says($messages);
    }

    /**
     * Send message to user.
     *
     * @param $messages
     * @return $this
     */
    public function says($messages)
    {
        $messages = $this->model->parseWithoutSave($messages);

        $this->request->sendMessages($messages);

        return $this;
    }

    /**
     * Find a response for current request
     *
     * @param String $message_type text or payload
     * @param String $ask Message or Payload name
     * @param string $data_set_type text, payload or default
     *
     * @return Node[]
     */
    private function findNodes($message_type, $ask, $data_set_type = 'text')
    {
        $nodes = Node::findByTypeAndPattern($message_type, $ask);

        if (empty($nodes)) {
            $nodes = Node::findByTypeAndPattern('default');
        }

        return $nodes;
    }

    /**
     * Response for intended actions
     *
     * @return bool
     */
    private function responseIntendedAction()
    {
        $waiting = $this->storage->get($this->getLeadId(), '_wait');

        // We set previous_waiting to back to support $bot->keep() method
        $this->conversation->set('previous_intended_action', $waiting);

        if ( ! empty($waiting)) {

            $this->storage->set($this->getLeadId(), '_wait', false);

            // Get Nodes for intended actions.
            if (is_numeric($waiting))
            {
                $nodes = Node::find($waiting);

                if ( ! empty($nodes))
                    $nodes = [$nodes];
            }
            else
            {
                $nodes = Node::findByTypeAndPattern('intended', $waiting);
            }

            $this->response($nodes);

            return true;
        }

        return false;
    }

    /**
     * Get user sent location
     *
     * @param string $output If provided, returns either `lat` or `long` of current location
     *
     * @return mixed
     */
    public function getLocation($output = '')
    {
        $attachments = $this->getAttachments();

        $location = new \stdClass();

        if ( ! empty($attachments) && isset($attachments[0]->type) && $attachments[0]->type === 'location')
            $location = $attachments[0]->payload->coordinates;

        if ( ! empty($output))
            return $location->$output;

        return $location;
    }

    /**
     * Get user attachments
     *
     * @return mixed
     */
    public function getAttachments()
    {
        if ($this->isUserMessage() && isset($this->message->attachments))
            return $this->message->attachments;

        return null;
    }


    public function getReceivedText()
    {
        if ($this->isUserMessage())
            return isset($this->message->text) ? $this->message->text : '';

        return '';
    }

    private function isUserMessage()
    {
        if ( ! empty($this->message))
            return $this->message->metadata != 'SENT_BY_GIGA_AI';
    }

    public function getLeadId()
    {
        return $this->conversation->get('lead_id', null);
    }

    /**
     * Save the auto stop state
     *
     * @param $event
     * @return bool
     */
    public function verifyAutoStop($event)
    {
        return false;
    }

    /**
     * Named Intended Action
     *
     * @param $action
     */
    public function wait($action)
    {
        $lead_id = Conversation::get('lead_id');

        // For chaining after $bot->say() method
        if ($lead_id != null)
            $this->storage->set($lead_id, '_wait', $action);

        // For chaining after $bot->answer() method
        else
            $this->model->addIntendedAction($action);
    }

    /**
     * Index Intended Action
     *
     * @param callable $callback
     * @return $this
     */
    public function then(callable $callback)
    {
        $this->model->addIntendedAction($callback);

        return $this;
    }

    /**
     * Keep staying in current intended action.
     *
     * @param $messages
     */
    public function keep($messages)
    {
        $previous_intended_action = Conversation::get('previous_intended_action');

        if ($previous_intended_action == null)
            return;

        $this->says($messages)->wait($previous_intended_action);
    }
}