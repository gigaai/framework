<?php

namespace GigaAI;

use GigaAI\Core\AccountLinking;
use GigaAI\Core\DynamicParser;
use GigaAI\Shared\CanLearn;
use GigaAI\Shortcodes\Shortcode;
use GigaAI\Storage\Eloquent\Instance;
use GigaAI\Storage\Storage;
use GigaAI\Http\Request;
use GigaAI\Conversation\Conversation;
use GigaAI\Core\Model;
use GigaAI\Core\Config;
use GigaAI\Resolver\Resolver;
use SuperClosure\Serializer;
use GigaAI\Storage\Eloquent\Node;
use GigaAI\Conversation\Nlp;
use GigaAI\Storage\Eloquent\Lead;

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
     * NLP helpers
     *
     * @var Nlp
     */
    public $nlp;

    /**
     * Resolver
     *
     * @since 3.0
     */
    protected $resolver;

    /**
     * Load the required resources
     *
     * @param $config
     */
    public function __construct($config = null)
    {
        // Framework Version
        if (!defined('GIGAAI_VERSION')) {
            define('GIGAAI_VERSION', '3.0');
        }

        // Make a conversation instance to share the data across whole application.
        $this->conversation = Conversation::getInstance();

        $this->conversation->set('token', strtotime('now'));

        // Setup the configuration data
        $this->config = Config::getInstance();

        if (!empty($config)) {
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

        // Giga AI Resolver
        $this->resolver = new Resolver;
    }

    /**
     * Run the bot
     */
    public function run()
    {
        $received = $this->request->getReceivedData();

        if (!$received || empty($received['object']) || $received['object'] != 'page') {
            return;
        }

        $this->received = $received;

        if (!isset($received['entry'])) {
            return;
        }

        foreach ($received['entry'] as $entry) {
            if (!isset($entry['messaging'])) {
                return;
            }

            foreach ($entry['messaging'] as $event) {
                $this->conversation->set([
                    'lead_id' => $event['sender']['id'],
                    'page_id' => $event['recipient']['id'],
                    'timestamp' => $event['timestamp'],
                ]);

                $this->processEvent($event);
            }
        }
    }

    /**
     * Process the event and response
     *
     * @param Array $event
     *
     * @return void
     */
    public function processEvent($event)
    {
        // Pass thread control to Inbox if Page Administrators move from Done to Inbox
        if (isset($event['request_thread_control'])) {
            return $this->passToInbox();
        }

        // Handle Account Linking and Unlinking
        if (isset($event['account_linking'])) {
            return AccountLinking::process($event);
        }

        // Handle message and postback
        if (!isset($event['message']) && !isset($event['postback'])) {
            return null;
        }

        if (isset($event['message'])) {
            $this->message = $event['message'];

            // Enabling NLP
            $this->initNlp($event['message']);
        }

        if (isset($event['postback'])) {
            $this->postback = $event['postback'];
        }

        // If current message is sent from Page
        if (isset($event['message']['is_echo'])) {
            $this->conversation->set('lead_id', $event['recipient']['id']);
            $this->conversation->set('page_id', $event['sender']['id']);
        }

        $this->conversation->set('received_input', $this->getReceivedInput());

        $this->setConfigData();

        // Save lead data if not exists.
        if (!isset($event['message']['is_echo'])) {
            $lead = $this->storage->pull();
        } else {
            $lead = Lead::withTrashed()->where('user_id', $this->getLeadId())->first();
        }

        $this->conversation->set('lead', $lead);
        
        // Message was sent by page, we don't need to response.
        if (isset($event['message']) && isset($event['message']['is_echo']) && $event['message']['is_echo'] == true) {
            return null;
        }

        DynamicParser::support([
            'type' => 'callback',
            'callback' => function ($content) use ($lead) {
                return $this->resolver->bind([
                    'bot' => $this,
                    'lead' => $lead,
                    'input' => $this->getReceivedInput(),
                ])->resolve($content);
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
     * Load NLP Data
     *
     * @return void
     */
    private function initNlp($message)
    {
        if (isset($message['nlp']) && is_array($message['nlp'])) {
            $this->nlp = new Nlp($message['nlp']);
            Conversation::set('nlp', $this->nlp);
        }
    }

    /**
     * Load page access token from database and set
     *
     * @return void
     */
    public function setConfigData()
    {
        $meta = Instance::get('meta');

        Config::set($meta);
    }

    /**
     * Response sender message
     *
     * @param      $nodes
     * @param null $lead_id
     */
    public function response($nodes, $lead_id = null)
    {
        foreach ($nodes as $node) {
            // Set intended action if this node has
            if (!empty($node->wait)) {
                $lead = $this->conversation->get('lead');

                $lead->data('_wait', $node->wait);
            }

            $answers = $this->parse($node->answers);

            $this->request->sendMessages($answers, [
                'messaging_type' => $node->messaging_type
            ]);
        }
    }

    /**
     * Find a response for current request
     *
     * @param String $message_type text or payload
     * @param String $ask Message or Payload name
     *
     * @return Node[]
     */
    public function findNodes($message_type, $ask)
    {
        $nodes = Node::findByTypeAndPattern($message_type, $ask);

        if ($nodes->count() === 0) {
            $nodes = Node::findByTypeAndPattern('default');
        }

        return $nodes->filter(function ($node) {
            $pageId = Conversation::get('page_id');

            return (empty($node['sources']) || (isset($node->sources['global']) && $node->sources['global'] == true)
                || (isset($node->sources[$pageId]) && $node->sources[$pageId] == true));
        });
    }

    /**
     * Response for intended actions
     *
     * @return bool
     */
    private function responseIntendedAction()
    {
        $lead = $this->conversation->get('lead');

        $waiting = $lead->_wait;

        // We set previous_waiting to back to support $bot->keep() method
        $this->conversation->set('previous_intended_action', $waiting);

        if (!empty($waiting)) {
            $lead->update([
                '_wait' => false
            ]);

            // Get Nodes for intended actions.
            if (is_numeric($waiting)) {
                $nodes = Node::find($waiting);

                if (!empty($nodes)) {
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

        if (!empty($attachments) && isset($attachments[0]['type']) && $attachments[0]['type'] === 'location') {
            $location = $attachments[0]['payload']->coordinates;
        }

        if (!empty($output)) {
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

    /**
     * Get user sent text
     *
     * @return string
     */
    public function getReceivedText()
    {
        if ($this->isUserMessage()) {
            return isset($this->message->text) ? $this->message->text : '';
        }

        return '';
    }

    /**
     * Get received input (user text or clicked button payload)
     *
     * @return mixed|null|void
     */
    public function getReceivedInput()
    {
        if (!$this->isUserMessage()) {
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
        if (!empty($this->message) && isset($this->message['metadata'])) {
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