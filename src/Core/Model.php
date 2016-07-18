<?php

namespace GigaAI\Core;

class Model
{
	public $answers = array(
		'text' => array(),
		'payload' => array(),
		'default' => array(),
		'welcome' => array(),
	);

	public $current_node = array();

	public function parseAnswers($asks, $answers = null)
	{
		if (is_string($asks))
		{
			$node_type = 'text';

			// If user set payload, default, welcome message.
			foreach (array('payload', 'default', 'welcome') as $type)
			{
				if (strpos($asks, $type . ':') !== false)
				{
					$node_type = $type;

					$asks = ltrim($asks, $type . ':');
				}
			}

			if (is_callable($answers))
			{
				$this->addAnswer(array(
					'type' => 'callback',
					'callback' => $answers
				), $node_type, $asks);

				return $this;
			}

			$answers = (array)$answers;

			// We will keep _wait format.
			if ( ! empty($answers['_wait']))
				return $this;

			// Short hand method of attachments
			if (isset($answers['buttons']) || isset($answers['elements'])
				|| isset($answers['title']) || isset($answers['text'])
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

		// We won't parse callback
		if (( ! isset($answer['type']) || $answer['type'] != 'callback') && ! isset($answer['_wait']))
			$answer = Parser::parseAnswer($answer);

		if ( ! isset($this->answers[$node_type][$asks]) && in_array($node_type, array('text', 'payload')))
			$this->answers[$node_type][$asks] = array();

		if (in_array($node_type, array('text', 'payload')))
			$this->answers[$node_type][$asks][] = $answer;

		if ($node_type === 'default')
			$this->answers['default'][] = $answer;

		if ($node_type === 'welcome')
			$this->answers['welcome'] = $answer;
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
			return array(Parser::parseAnswer($answers));

		return array_map(array('Parser', 'parseAnswer'), $answers);
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