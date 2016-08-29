<?php

namespace GigaAI;

use GigaAI\Storage\Storage;
use GigaAI\Http\Request;
use GigaAI\Core\Parser;
use GigaAI\Core\Model;
use GigaAI\Core\Config;

class MessengerBot
{
	public $request;

	public $storage;

	public $sender_id;

	public $recipient_id;

	public $received_text;

	private $model;

	public $config;

	private $message;

    private $event;

	public function __construct(array $config = array())
	{
		$this->config = Config::getInstance();

		if ( ! empty($config))
			$this->config->set($config);

		$this->request = new Request;

		$this->storage = new Storage;

		$this->model = new Model;

	}

	public function answer($ask, $response = null)
	{
		return $this->answers($ask, $response);
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

	public function getAnswers($node_type = null, $asks = '')
	{
		return $this->model->getAnswers($node_type, $asks);
	}

	public function run()
	{
		$received = $this->request->getReceivedData();

		if ( ! $received || empty($received->object) || $received->object != 'page')
			return;

		$this->received = $received;

		foreach ($received->entry as $entry) {
			foreach ($entry->messaging as $event) {
				$this->sender_id = $event->sender->id;
				$this->recipient_id = $event->recipient->id;
				$this->timestamp = $event->timestamp;

				$this->received_text = isset($event->message->text) ? $event->message->text : null;

				$this->responseEvent($event);
			}
		}
	}

	public function responseEvent($event)
	{
		// Currently, we only handle message and postback
		if ( ! isset($event->message) && ! isset($event->postback))
			return;

        if (isset($event->message))
            $this->message = $event->message;

		// Save user data if not exists.
		$this->storage->pull($event);

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
	 * @param $node
	 * @param null $sender_id
	 */
	public function response($node, $sender_id = null)
	{
		if (is_null($sender_id))
			$sender_id = $this->sender_id;

		$node = (array)$node;

		foreach ($node as $response) {
			if (isset($response['type']) && $response['type'] === 'callback' && is_callable($response['callback'])) {

				@call_user_func_array($response['callback'], array($this, $this->getUserId(), $this->getReceivedText()));

				continue;
			}

			if ( ! empty($response['_wait']) && is_string($response['_wait'])) {
				$this->storage->set($sender_id, '_wait', $response['_wait']);

				continue;
			}

			$response = Parser::parseShortcodes($response, $this->storage->get($sender_id));

			$response['metadata'] = 'SENT_BY_GIGA_AI';

			$body = array(
				'recipient' => array(
					'id' => $sender_id
				),
				'message' => $response
			);


			$this->request->send("https://graph.facebook.com/v2.6/me/messages?access_token=" . $this->config->get('page_access_token'), $body);
		}
	}

	/**
	 * Alias of says() method
	 *
	 * @param $messages
	 */
	public function say($messages)
	{
		return $this->says($messages);
	}

	/**
	 * Send message to user.
	 *
	 * @param $messages
	 */
	public function says($messages)
	{
		$messages = $this->model->addReply($messages);

		$this->response($messages);

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
		if ($this->responseIntendedAction($message_type))
			return;

		$data_set = $this->getAnswers($message_type);

		$marked = false;

		foreach ($data_set as $node_name => $node_content) {
			if ( ! giga_match($node_name, $ask))
				continue;

			$this->response($node_content);

			$marked = true;
		}

		// If not found any response. Run this method again to send default message.
		if ( ! $marked)
			$this->response($this->getAnswers('default'));
	}

	/**
	 * Response for intended actions
	 *
	 * @param $message_type
	 * @return bool
	 */
	private function responseIntendedAction($message_type)
	{
		$waiting = $this->storage->get($this->sender_id, '_wait');

		if ( ! empty($waiting) && is_string($waiting)) {

			$this->storage->set($this->sender_id, '_wait', false);

			$this->response($this->getAnswers($message_type, '@' . $waiting));

			return true;
		}

		return false;
	}

	/**
	 * Wait for intended actions
	 *
	 * @param $action
	 * @param string $message_type
	 */
	public function wait($action, $message_type = '')
	{
		if (isset($this->sender_id) && ! empty($this->sender_id))
			$this->storage->set($this->sender_id, '_wait', $action);
		else
			$this->model->addIntendedAction($action, $message_type);
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
    }


	public function getReceivedText()
    {
        if ($this->isUserMessage())
            return isset($this->message->text) ? $this->message->text : '';

        return '';
    }

    private function isUserMessage()
    {
        return $this->message->metadata != 'SENT_BY_GIGA_AI';
    }

    public function getUserId()
    {
        if ($this->isUserMessage())
            return $this->sender_id;
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
}