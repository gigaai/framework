<?php

namespace GigaAI;

use PHPUnit\Framework\TestCase;

class MessengerBotTest extends TestCase
{

	public function testSetup()
	{
		$bot = new MessengerBot;

		$answers = $bot->getAnswers();

		$this->assertArrayHasKey('text', $answers);
		$this->assertArrayHasKey('default', $answers);
		$this->assertArrayHasKey('payload', $answers);

		return $bot;
	}
	/** 
	 * @depends testSetup
	 */
	public function testText(MessengerBot $bot)
	{
		$bot = new MessengerBot;

		$bot->answer('foo', 'Bar');

		$answers = $bot->getAnswers('text', 'foo');

		$this->assertEquals($answers, [
			[
				'text' => 'Bar'
			]
		]);

		return $bot;
	}

	/** 
	 * @depends testText
	 */
	public function testTextInArray(MessengerBot $bot)
	{
		$bot->answer('bar', ['Bar']);

		$answers = $bot->getAnswers('text', 'bar');

		$this->assertEquals($answers, [
			[
				'text' => 'Bar'
			]
		]);

		return $bot;
	}

	/**
	 * @depends testText
	 */
	public function testMultipleText(MessengerBot $bot)
	{
		$bot->answer('foo', ['Jame', 'Annie']);

		$answers = $bot->getAnswers('text', 'foo');

		$this->assertCount(3, $answers);

		$this->assertEquals($answers, [
			[
				'text' => 'Bar'
			],
			[
				'text' => 'Jame'
			],
			[
				'text' => 'Annie'
			]
		]);

		return $bot;
	}
}