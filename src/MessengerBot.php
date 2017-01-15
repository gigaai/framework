<?php

namespace GigaAI;

use GigaAI\Conversation\AutoStop;
use GigaAI\Core\AccountLinking;
use GigaAI\Core\DynamicParser;
use GigaAI\Shared\CanLearn;
use GigaAI\Storage\Storage;
use GigaAI\Http\Request;
use GigaAI\Conversation\Conversation;
use GigaAI\Core\Model;
use GigaAI\Core\Config;
use SuperClosure\Serializer;
use GigaAI\Storage\Eloquent\Node;
use GigaAI\Subscription\Subscription;

class MessengerBot
{
    use CanLearn;
    
    /**
     * Request instance
     *
     * @var Request|static
     */
    public $request;
    
    /**
     * Storage instance
     *
     * @var Storage
     */
    public $storage;
    
    /**
     * Config instance
     *
     * @var Config
     */
    public $config;
    
    /**
     * Conversation instance
     *
     * @var Conversation
     */
    public $conversation;
    
    /**
     * Received request
     *
     * @var object
     */
    public $received;
    
    /**
     * Subscription instance
     *
     * @var Subscription
     */
    public $subscription;
    
    /**
     * Model parser
     *
     * @var Model
     */
    private $model;
    
    /**
     * Current parsing message
     *
     * @var array
     */
    private $message;
    
    /**
     * Serializer instance
     *
     * @var Serializer
     */
    private $serializer;
    
    /**
     * Load the required resources
     *
     * @param $instance
     */
    public function __construct($instance = null)
    {
        // Package Version
        if ( ! defined('GIGAAI_VERSION')) {
            define('GIGAAI_VERSION', '2.2.1');
        }
        
        // Setup the configuration data
        $this->config = Config::getInstance();
        if ( ! empty($config)) {
            $this->config->set($config);
        }
        
        // Make a Request instance. Not required but it will help user use $bot->request syntax
        $this->request = Request::getInstance();
        
        // Make a Session instance. Not required but it will help user use $bot->session syntax
        $this->conversation = Conversation::getInstance();
        
        // Load the storage
        $this->storage = new Storage;
        
        // Load the model
        $this->model = new Model;
        
        // We need to serialize Closure for dynamic data and intended actions
        $this->serializer = new Serializer();
        
        // Boot the subscription feature
        $this->subscription = Subscription::getInstance();
    }
    
    /**
     * Run the bot
     */
    public function run()
    {
        $received = $this->request->getReceivedData();
        
        if ( ! $received || empty($received->object) || $received->object != 'page') {
            return;
        }
        
        $this->received = $received;
        
        foreach ($received->entry as $entry) {
            
            foreach ($entry->messaging as $event) {
                $this->conversation->set([
                    'sender_id'    => $event->sender->id,
                    'recipient_id' => $event->recipient->id,
                    'timestamp'    => $event->timestamp,
                ]);
                
                $this->processEvent($event);
            }
        }
    }
    
    public function processEvent($event)
    {
        // Handle Account Linking and Unlinking
        if (isset($event->account_linking)) {
            return AccountLinking::process($event);
        }
        
        // Handle message and postback
        if ( ! isset($event->message) && ! isset($event->postback)) {
            return null;
        }
        
        if (isset($event->message)) {
            $this->message = $event->message;
        }
        
        // If auto stop is run and it return true. Terminate
        if (AutoStop::run($event)) {
            return null;
        }
        
        // If current message is send from Lead
        if ( ! $this->conversation->has('lead_id') && $event->sender->id != Config::get('page_id')) {
            $this->conversation->set('lead_id', $event->sender->id);
            
            if (AutoStop::isStopped()) {
                return null;
            }
            
            // Save lead data if not exists.
            $this->storage->pull($event->sender->id);
        }
        
        // Message was sent by page, we don't need to response.
        if (isset($event->message) && isset($event->message->is_echo) && $event->message->is_echo == true) {
            return null;
        }
        
        DynamicParser::support([
            'type'     => 'callback',
            'callback' => function ($content) {
                return @call_user_func_array($content, [$this, $this->getLeadId(), $this->getReceivedText()]);
            },
        ]);
        
        $type_pattern = $this->request->getTypeAndPattern($event);
        
        // We'll check to response intended action first
        if ($this->responseIntendedAction()) {
            return;
        }
        
        $nodes = $this->findNodes($type_pattern['type'], $type_pattern['pattern']);
        
        $this->response($nodes);
    }
    
    /**
     * Response sender message
     *
     * @param      $nodes
     * @param null $lead_id
     */
    public function response($nodes, $lead_id = null)
    {
        if (is_null($lead_id)) {
            $lead_id = $this->conversation->get('lead_id');
        }
        
        foreach ($nodes as $node) {
            
            /** New wait */
            if ( ! empty($node->wait)) {
                $this->storage->set($lead_id, '_wait', $node->wait);
            }
            
            // Backward compatibility
            if (array_key_exists('type', $node->answers)) {
                $node->answers = [$node->answers];
            }
            
            foreach ($node->answers as $answer) {
                
                /** Process dynamic content */
                if (isset($answer['type'])) {
                    
                    // Backward compatibility
                    if (isset($answer['callback'])) {
                        $answer['content'] = $answer['callback'];
                    }
                    
                    if (is_string($answer['content']) && giga_match('%SerializableClosure%', $answer['content'])) {
                        $answer['content'] = $this->serializer->unserialize($answer['content']);
                    }
                    
                    $return = DynamicParser::parse($answer);
                    
                    // If the callback return, we'll send that message to user.
                    if ($return != null || ! empty($return)) {
                        $answer = $this->model->parseWithoutSave($return);
                        
                        // Answer == 0 means that answers is already parsed and it's a single message.
                        if ($answer != false) {
                            $this->request->sendMessages($answer);
                        } else {
                            $this->request->sendMessage($return);
                        }
                        
                        continue;
                    }
                }
                
                $this->request->sendMessage($answer);
            }
        }
    }
    
    /**
     * Find a response for current request
     *
     * @param String $message_type  text or payload
     * @param String $ask           Message or Payload name
     * @param string $data_set_type text, payload or default
     *
     * @return Node[]
     */
    public function findNodes($message_type, $ask, $data_set_type = 'text')
    {
        $nodes = Node::findByTypeAndPattern($message_type, $ask);
        
        if ($nodes->count() === 0) {
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
            if (is_numeric($waiting)) {
                $nodes = Node::find($waiting);
                
                if ( ! empty($nodes)) {
                    $nodes = [$nodes];
                }
            } else {
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
        
        if ( ! empty($attachments) && isset($attachments[0]->type) && $attachments[0]->type === 'location') {
            $location = $attachments[0]->payload->coordinates;
        }
        
        if ( ! empty($output)) {
            return $location->$output;
        }
        
        return $location;
    }
    
    /**
     * Get user attachments
     *
     * @return mixed
     */
    public function getAttachments()
    {
        if ($this->isUserMessage() && isset($this->message->attachments)) {
            return $this->message->attachments;
        }
        
        return null;
    }
    
    
    public function getReceivedText()
    {
        if ($this->isUserMessage()) {
            return isset($this->message->text) ? $this->message->text : '';
        }
        
        return '';
    }
    
    private function isUserMessage()
    {
        if ( ! empty($this->message)) {
            return $this->message->metadata != 'SENT_BY_GIGA_AI';
        }
    }
    
    public function getLeadId()
    {
        return $this->conversation->get('lead_id', null);
    }
    
    /**
     * Save the auto stop state
     *
     * @param $event
     *
     * @return bool
     */
    public function verifyAutoStop($event)
    {
        return false;
    }
    
    /**
     * Set tag the for the node
     *
     * @param $tag
     *
     * @return $this
     */
    public function taggedAs($tag)
    {
        $this->model->taggedAs($tag);
        
        return $this;
    }
}