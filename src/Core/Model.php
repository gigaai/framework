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
					array(
						'type' => 'callback',
						'callback' => $answers
					),
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

		// Todo: Support code from WordPress
		// Recursive if we set multiple asks, responses
		if (is_array($asks) && is_null($answers))
		{
			foreach ($asks as $ask => $answers)
			{
				$this->answers($ask, $answers);
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

		if ( ! isset($this->answers[$node_type][$asks]) && in_array($node_type, array('text', 'payload')))
			$this->answers[$node_type][$asks] = array();

		if (in_array($node_type, array('text', 'payload')))
			$this->answers[$node_type][$asks][] = $answer;

		if ($node_type === 'default')
			$this->answers['default'][] = $answer;
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

	public function getAnswers($node_type = null, $ask = '')
	{
		if (empty($node_type) || ! isset($this->answers[$node_type]))
			return $this->answers;

		$answers = $this->answers[$node_type];

		if (empty($ask))
			return $answers;

		return $answers[$ask];
	}

	public function addReply($answers)
	{
		// Short hand method of attachments
		if (isset($answers['buttons']) || isset($answers['elements'])
			|| isset($answers['title']) || isset($answers['text']) || is_string($answers)
		)
		{
			return array(Parser::parseAnswer($answers));
		}

		$output = array();

		foreach ($answers as $answer)
		{
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