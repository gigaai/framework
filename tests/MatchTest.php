<?php

namespace GigaAI;

use PHPUnit\Framework\TestCase;

class MatchTest extends TestCase
{
	public function testMatch()
	{
		$pattern = 'my string';

		$user_text = 'my string';

		$this->assertGreaterThan(0, giga_match($pattern, $user_text));
	}

	public function testSqlMatch()
	{
		$pattern = '%buy%gun%';

		$this->assertGreaterThan(0, giga_match($pattern, 'buy gun'));

		$this->assertGreaterThan(0, giga_match($pattern, 'buy gun now'));

		$this->assertGreaterThan(0, giga_match($pattern, 'I want to buy this GUN'));

		$this->assertEquals(0, giga_match($pattern, 'buy'));

		$this->assertEquals(0, giga_match($pattern, 'gun buy'));
	}

	public function testRegularExpression()
	{
		$pattern = 'regex:/[0-9]+/';

		$this->assertEquals(1, giga_match($pattern, 1234));

		$this->assertEquals(1, giga_match($pattern, '12345'));

		$this->assertEquals(0, giga_match($pattern, '?foobar'));
	}

	public function testPipelines()
	{
		$pattern = '(foo|bar)';

		$this->assertEquals(1, giga_match($pattern, 'foo'));

		$this->assertEquals(0, giga_match($pattern, 'baz'));
	}
}