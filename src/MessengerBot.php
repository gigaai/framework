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

	public function __construct(array $config = array())
	{
		$this->config = Config::getInstance();

		if (! empty($config))
			$this->config->set($config);

		$this->request = new Request;

		$this->storage = new Storage;

		$this->model = new Model;

		// Get verify token and print verify message
		$this->request->verifyTokenFromFacebook();
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

		if ($received->object != 'page' || ! $received)
			return;

		$this->received = $received;

		foreach ($received->entry as $entry)
		{
			foreach ($entry->messaging as $event)
			{
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
		if ( ! ($event->message || $event->postback))
			return;

		// If human reply with Text. Stop the bot, with empty text. Re-enable the bot.
		if ($this->verifyAutoStop($event))
			return;

		// Save user data if not exists.
		$this->storage->pull($event);

		$message_type = $event->message ? 'text' : 'payload';

		$ask = $event->message ? $event->message->text : $event->postback->payload;

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

		foreach ($node as $response)
		{
			if (isset($response['type']) && $response['type'] === 'callback' && is_callable($response['callback']))
			{
				@call_user_func_array($response['callback'], array($this));

				continue;
			}

			if ( ! empty($response['_wait']) && is_string($response['_wait']))
			{
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

		if ($data_set_type === 'default')
		{
			$this->response($data_set['default']);

			return;
		}

		$marked = false;

		foreach ($data_set as $node_name => $node_content)
		{
			if ( ! giga_match($node_name, $ask))
				continue;

			$this->response($node_content);

			$marked = true;
		}

		// If not found any response. Run this method again to send default message.
		if ( ! $marked)
			$this->findAndResponse($ask, $this->answers['default'], 'default');
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

		if ( ! empty($waiting) && is_string($waiting))
		{
			$this->response($this->getAnswers($message_type, '@' . $waiting));

			$this->storage->set($this->sender_id, '_wait', false);

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
		$this->model->addIntendedAction($action, $message_type);
	}

	/**
	 * Save the auto stop state
	 *
	 * @param $event
	 * @return bool
	 */
	public function verifyAutoStop($event)
	{
		if ($event->message && $event->message->is_echo && ! isset($event->message->app_id))
		{
			$auto_stop = $event->message->text != '';

			$this->storage->set($event->recipient->id, 'auto_stop', $auto_stop);

			return true;
		}

		$auto_stop = $this->storage->get($event->sender->id, 'auto_stop', 0);

		return $auto_stop;
	}
}