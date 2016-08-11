<?php

namespace GigaAI;

use PHPUnit\Framework\TestCase;

class MatchTest extends TestCase
{
	public function testMatch()
	{
		$pattern = 'my string';

		$user_text = 'my string';

		$this->assertTrue(giga_match($pattern, $user_text));
	}

	public function testSqlMatch()
	{
		$pattern = '%buy%gun%';

		$this->assertTrue(giga_match($pattern, 'buy gun'));

		$this->assertTrue(giga_match($pattern, 'buy gun now'));

		$this->assertTrue(giga_match($pattern, 'I want to buy this GUN'));

		$this->assertFalse(giga_match($pattern, 'buy'));

		$this->assertFalse(giga_match($pattern, 'gun buy'));
	}

	public function testRegularExpression()
	{
		$pattern = 'regex:/[0-9]+/';

		$this->assertTrue(giga_match($pattern, 1234));

		$this->assertTrue(giga_match($pattern, '12345'));

		$this->assertFalse(giga_match($pattern, '?foobar'));
	}
}