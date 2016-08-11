<?php

namespace GigaAI;

use PHPUnit\Framework\TestCase;

class ButtonTest extends TestCase
{
	private function getSampleButton()
	{
		return [
			'text' => 'Button CTA Text',
			'buttons' => [
				[
					'type' => 'web_url',
					'url' => 'http://google.com',
					'title' => 'Button 1'
				],
				[
					'type' => 'postback',
					'payload' => 'MY_BUTTON_PAYLOAD',
					'title' => 'Button 2'
				]
			]
		];
	}

	private function generateStandardButton($button_template)
	{
		$mixed = [
			'attachment' => [
				'type' => 'template'
			]
		];

		$mixed['attachment']['payload'] = $button_template;
		$mixed['attachment']['payload']['template_type'] = 'button';

		return $mixed;
	}

	public function testButton()
	{
		$bot = new MessengerBot;

		$button_template = $this->getSampleButton();

		$bot->answer('button', $button_template);

		$answers = $bot->getAnswers('text', 'button');
		$answer = $answers[0];

		$this->assertArrayHasKey('attachment', $answer);
		$this->assertArrayHasKey('payload', $answer['attachment']);
		$this->assertArrayHasKey('type', $answer['attachment']);

		$mixed = $this->generateStandardButton($button_template);

		$this->assertEquals($mixed, $answer);
	}

	public function testButtonWithAnotherText()
	{
		$bot = new MessengerBot;

		$button_template = $this->getSampleButton();

		$bot->answer('button', [
			'Rick Grimes',
			$button_template
		]);

		$answers = $bot->getAnswers('text', 'button');
		$button = $answers[1];

		$this->assertArrayHasKey('attachment', $button);
		$this->assertArrayHasKey('payload', $button['attachment']);
		$this->assertArrayHasKey('type', $button['attachment']);

		$mixed = $this->generateStandardButton($button_template);

		$this->assertEquals($mixed, $button);

		$rick_grimes = $answers[0];

		$this->assertEquals([
			'text' => 'Rick Grimes'
		], $rick_grimes);
	}

	public function testRequiredFieldsShouldBeFilled()
	{
		return false;
	}

	public function testNoConflictBetweenPostbackandWebUrl()
	{
		
	}
}