<?php

namespace GigaAI;

use GigaAI\Storage\Storage;
use GigaAI\Http\Request;
use GigaAI\Http\Session;
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

    public $session;

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
        $this->session = Session::getInstance();

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
                $this->session->set([
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

            if ( ! isset($event->message->metadata) || $event->message->metadata != 'SENT_BY_GIGA_AI') {

                if ( ! $this->session->has('lead_id')) {
                    $this->session->set('lead_id', $event->sender->id);

                    // Save user data if not exists.
                    $this->storage->pull($event->sender->id);
                }
            }
        }

        $message_type = 'text';
        $ask = '';

        if (isset($event->message) && isset($event->message->text))
            $ask = $event->message->text;

        if (isset($event->message) && isset($event->message->attachments)) {
            $message_type = 'attachment';

            if (isset($event->message->attachments[0]->type))
                $ask = $event->message->attachments[0]->type;
        }

        if (isset($event->postback->payload)) {
            $message_type = 'payload';
            $ask = $event->postback->payload;
        }

		$this->findAndResponse($message_type, $ask);
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
			$lead_id = $this->session->get('lead_id');

		foreach ($nodes as $node) {

		    /** Process callback */
			if (! empty($node->answers) && isset($node->answers['type']) && $node->answers['type'] === 'callback') {

			    $callback = $this->serializer->unserialize($node->answers['callback']);

                if (is_callable($callback)) {
                    $return = @call_user_func_array($callback, [$this, $this->getLeadId(), $this->getReceivedText()]);

                    // If the callback return, we'll send that message to user.
                    if ($return != null || ! empty($return))
                    {
                        $return = $this->model->parseWithoutSave($return);

                        foreach ($return as $message) {
                            $this->request->sendMessage($message);
                        }
                    }
                }

                continue;
			}

            /** New wait */
            if ( ! empty($node->wait)) {
                $this->storage->set($lead_id, '_wait', $node->wait);
            }

			foreach ($node->answers as $response) {

                /** Support old _wait */
                if (!empty($response['_wait']) && is_string($response['_wait'])) {
                    $this->storage->set($lead_id, '_wait', $response['_wait']);

                    continue;
                }

                $this->request->sendMessage($response);
            }
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
	 * @return void
	 */
	private function findAndResponse($message_type, $ask, $data_set_type = 'text')
	{
		// We'll check to response intended action first
		if ($this->responseIntendedAction())
			return;

		$nodes = Node::findByTypeAndPattern($message_type, $ask);

        if (empty($nodes)) {
            $nodes = Node::findByTypeAndPattern('default');
        }

        $this->response($nodes);
	}

	/**
	 * Response for intended actions
	 *
	 * @return bool
	 */
	private function responseIntendedAction()
	{
		$waiting = $this->storage->get($this->sender_id, '_wait');

		if ( ! empty($waiting)) {

			$this->storage->set($this->sender_id, '_wait', false);

            $nodes = Node::findByTypeAndPattern('intended', $waiting);

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
        return $this->session->get('lead_id', null);
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
     * Wait for intended actions
     *
     * @param $action
     */
    public function wait($action)
    {
        // For chaining after $bot->say() method
        if (isset($this->sender_id) && ! empty($this->sender_id))
            $this->storage->set($this->sender_id, '_wait', $action);

        // For chaining after $bot->answer() method
        else
            $this->model->addIntendedAction($action);
    }

	public function then(callable $callback)
    {
        $this->model->addIntendedAction($callback);

        return $this;
    }

    public function keep($messages)
    {
        $this->says($messages);

        //
    }
}