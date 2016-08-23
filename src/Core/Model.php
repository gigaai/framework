<?php

namespace GigaAI\Core;

class Model
{
	public $answers = array(
		'text' => array(),
		'payload' => array(),
		'default' => array(),
	);

	public $current_node = array();

	public function parseAnswers($asks, $answers = null)
	{
		if (is_string($asks))
		{
			$node_type = 'text';

			// If user set payload, default, welcome message.
			foreach (array('payload', 'default') as $type)
			{
				if (strpos($asks, $type . ':') !== false)
				{
					$node_type = $type;

					$asks = ltrim($asks, $type . ':');
				}
			}

			if (is_callable($answers))
			{
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
			if (array_key_exists('buttons', $answers) ||
				array_key_exists('elements', $answers) || // For Generic or Receipt
				(is_array($answers[0]) && array_key_exists('title', $answers[0])) || // For Generic
				array_key_exists('text', $answers) || // For Button
				array_key_exists('type', $answers) ||
				array_key_exists('quick_replies', $answers)
			)
			{
				$this->addAnswer($answers, $node_type, $asks);

				return $this;
			}

			foreach ($answers as $answer)
			{
				$this->addAnswer($answer, $node_type, $asks);
			}
		}

		// Recursive if we set multiple asks, responses
		if (is_array($asks) && is_null($answers))
		{
			if (array_key_exists('text', $asks) && array_key_exists('payload', $asks))
			{
				foreach ($asks as $event => $nodes)
				{
					$prepend = $event === 'text' ? '' : $event . ':';
					if ($event === 'default')
						$nodes = array($nodes);
					foreach ($nodes as $ask => $responses)
					{
						$this->parseAnswers($prepend . $ask, $responses);
					}
				}
			}
			else {
				foreach ($asks as $ask => $answers)
				{
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
		
		// We won't parse callback and parsed content. Note that PHP < 5.4 will treat string as array.
		if ($this->isParsable($answer))
			$answer = Parser::parseAnswer($answer);

		$storage_driver = Config::get('storage_driver', 'file');

		if ($storage_driver === 'file' || $answer['type'] === 'callback') {
			if ( ! isset( $this->answers[ $node_type ][ $asks ] ) &&
			     in_array( $node_type, array( 'text', 'payload' ) )
			) {
				$this->answers[ $node_type ][ $asks ] = array();
			}

			if ( in_array( $node_type, array( 'text', 'payload' ) ) ) {
				$this->answers[ $node_type ][ $asks ][] = $answer;
			}

			if ( $node_type === 'default' ) {
				$this->answers['default'][] = $answer;
			}
		}
		else
		{
			\GigaAI\Storage\Storage::addAnswer($answer, $node_type, $asks);
		}
	}

	private function isParsable($answer)
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
		{
			$search_in_storage = \GigaAI\Storage\Storage::getAnswers($node_type, $ask);
		}

		$answers = array_merge_recursive($search_in_storage, $this->answers);

		if ( ! empty($node_type) && ! empty($answers[$node_type]))
			$answers = $answers[$node_type];

		if ( ! empty($node_type) && ! empty($ask) && ! empty($answers[$node_type][$ask]))
			$answers = $answers[$node_type][$ask];

		return $answers;
	}

	public function addReply($answers)
	{
		// Short hand method of attachments
		if (isset($answers['buttons']) || isset($answers['elements'])
			|| isset($answers['title']) || isset($answers['text']) || is_string($answers)
		    || array_key_exists('quick_replies', $answers)
		)
			return array(Parser::parseAnswer($answers));

		$output = array();

		foreach ($answers as $answer) {
			$output[] = Parser::parseAnswer($answer);
		}

		return $output;
	}

	public function addIntendedAction($action, $message_type = '')
	{
		if (empty($this->current_node['node_type']) || $this->current_node['node_type'] == 'welcome')
			return;

		$this->addAnswer(
			array('_wait' => $action),
			$this->current_node['node_type'],
			$this->current_node['asks']
		);
	}
}