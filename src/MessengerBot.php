<?php

namespace GigaAI;

use GigaAI\Conversation\AutoStop;
use GigaAI\Core\AccountLinking;
use GigaAI\Core\DynamicParser;
use GigaAI\Shared\CanLearn;
use GigaAI\Storage\Eloquent\Instance;
use GigaAI\Storage\Storage;
use GigaAI\Http\Request;
use GigaAI\Conversation\Conversation;
use GigaAI\Core\Model;
use GigaAI\Core\Config;
use SuperClosure\Serializer;
use GigaAI\Storage\Eloquent\Node;
use GigaAI\Subscription\Subscription;
use GigaAI\Drivers\Driver;

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
    public $message;
    
    /**
     * Current parsing postback
     *
     * @var array
     */
    public $postback;
    
    /**
     * Serializer instance
     *
     * @var Serializer
     */
    private $serializer;
    
    /**
     * Load the required resources
     *
     * @param $config
     */
    public function __construct($config = null)
    {
        // Framework Version
        if ( ! defined('GIGAAI_VERSION')) {
            define('GIGAAI_VERSION', '2.3');
        }
        
        // Make a conversation instance to share the data across whole application.
        $this->conversation = Conversation::getInstance();
        
        $this->conversation->set('token', strtotime('now'));
        
        // Setup the configuration data
        $this->config = Config::getInstance();

        if ( ! empty($config)) {
            $this->config->set($config);
        }
        
        // Load request instance
        $this->request = Request::getInstance();

        // Load the storage
        $this->storage = new Storage;
        
        // Load the model
        $this->model = new Model;
        
        // We need to serialize Closure for dynamic data and intended actions
        $this->serializer = new Serializer;
        
        // Boot the subscription feature
        $this->subscription = Subscription::getInstance();
    }
    
    /**
     * Run the bot
     */
    public function run()
    {
        $received = $this->request->getReceivedData();
        
        if ( ! $received || empty($received['object']) || $received['object'] != 'page') {
            return;
        }
        
        $this->received = $received;
        
        if ( ! isset($received['entry'])) {
            return;
        }
        
        foreach ($received['entry'] as $entry) {
            
            if ( ! isset($entry['messaging'])) {
                return;
            }
            
            foreach ($entry['messaging'] as $event) {
                $this->conversation->set([
                    'sender_id'    => $event['sender']['id'],
                    'recipient_id' => $event['recipient']['id'],
                    'timestamp'    => $event['timestamp'],
                ]);
                
                $this->processEvent($event);
            }
        }
    }
    
    /**
     * Process the event and response
     *
     * @param Array $event
     * @return void
     */
    public function processEvent($event)
    {
        // Handle Account Linking and Unlinking
        if (isset($event['account_linking'])) {
            return AccountLinking::process($event);
        }
        
        // Handle message and postback
        if ( ! isset($event['message']) && ! isset($event['postback'])) {
            return null;
        }
        
        if (isset($event['message'])) {
            $this->message = $event['message'];
        }
        
        if (isset($event['postback'])) {
            $this->postback = $event['postback'];
        }
        
        $this->conversation->set('lead_id', $event['sender']['id']);
        $this->conversation->set('page_id', $event['recipient']['id']);
        
        // If current message is sent from Page
        if (isset($event['message']['is_echo'])) {
            $this->conversation->set('lead_id', $event['recipient']['id']);
            $this->conversation->set('page_id', $event['sender']['id']);
        }
        
        $this->conversation->set('received_input', $this->getReceivedInput());
        
        // If auto stop is run and it return true. Terminate
        if (AutoStop::run($event)) {
            return null;
        }
        
        if (AutoStop::isStopped()) {
            return null;
        }
        
        $this->setAccessToken();
        
        // Save lead data if not exists.
        if ( ! isset($event['message']['is_echo'])) {
            $this->storage->pull();
        }
        // Message was sent by page, we don't need to response.
        if (isset($event['message']) && isset($event['message']['is_echo']) && $event['message']['is_echo'] == true) {
            return null;
        }
        
        DynamicParser::support([
            'type'     => 'callback',
            'callback' => function ($content) {
                return @call_user_func_array($content, [$this, $this->getLeadId(), $this->getReceivedInput()]);
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
     * Load page access token from database and set
     */
    public function setAccessToken()
    {
        $is_multipage = Config::get('multipage');
        
        if ($is_multipage) {
            $access_token = Instance::get('page_access_token');
            
            Config::set('page_access_token', $access_token);
            
            Request::$token = $access_token;
        }
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
     * @param String $message_type text or payload
     * @param String $ask          Message or Payload name
     *
     * @return Node[]
     */
    public function findNodes($message_type, $ask)
    {
        $nodes = Node::findByTypeAndPattern($message_type, $ask);
        
        if ($nodes->count() === 0) {
            $nodes = Node::findByTypeAndPattern('default');
        }
        
        // Remove all nodes which are owned by other pages
        foreach ($nodes as $index => $node) {
            if (empty($node->source)) {
                continue;
            }
            
            $sources = array_map('trim', explode(',', $node->source));
            
            if ( ! in_array(Conversation::get('page_id'), $sources)) {
                unset($nodes[$index]);
            }
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
        
        if ( ! empty($attachments) && isset($attachments[0]['type']) && $attachments[0]['type'] === 'location') {
            $location = $attachments[0]['payload']->coordinates;
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
    
    public function getReceivedInput()
    {
        if ( ! $this->isUserMessage()) {
            return;
        }
        
        if (isset($this->message['text'])) {
            return $this->message['text'];
        }
        
        if (isset($this->postback['payload'])) {
            return $this->postback['payload'];
        }
        
        return null;
    }
    
    /**
     * Check if current message is sent by user
     *
     * @return boolean
     */
    private function isUserMessage()
    {
        if ( ! empty($this->message) && isset($this->message['metadata'])) {
            return $this->message['metadata'] != 'SENT_BY_GIGA_AI';
        }
        
        return true;
    }
    
    /**
     * Get lead id
     *
     * @return void
     */
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