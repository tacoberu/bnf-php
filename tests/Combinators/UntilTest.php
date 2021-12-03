<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Ref;
use Taco\BNF\Parser;
use PHPUnit\Framework\TestCase;


class UntilTest extends TestCase
{

	function testUntil_1()
	{
		$input = '
		abcd125a-x	@email name@domain.tld
			@author John Dee
		';
		$parser = new Until('value', [
			'~[a-z][a-zA-Z0-9\-]*~',
			//~ new Pattern('name', ['~[a-z][a-zA-Z0-9\-]*~']),
		]);

		list($token, $expected) = $parser->scan($input, 13, []);
		$this->assertEquals([], $expected);
		$this->assertEquals("\t@", $token->content);
		$this->assertEquals(13, $token->start);
		$this->assertEquals(15, $token->end);
	}



	function testUntil_2()
	{
		$input = 'abcd125a-x';
		$parser = new Until('value', [
			'~[a-z][a-zA-Z0-9\-]*~',
		]);

		list($token, $expected) = $parser->scan($input, 13, []);
		$this->assertFalse($token);
		$this->assertEquals([], $expected);
	}



	function testUntil_3()
	{
		$input = "abcdef\nqwerty\nuioúp";
		$parser = new Until('value', [
			'~\n~',
		]);

		list($token, $expected) = $parser->scan($input, 3, []);
		$this->assertEquals([], $expected);
		$this->assertEquals("def", $token->content);
		$this->assertEquals(3, $token->start);
		$this->assertEquals(6, $token->end);
	}



	function testUntil_4()
	{
		$input = 'abcdef';
		$parser = new Until('value', [
			'~[0-9]+~',
		]);

		list($token, $expected) = $parser->scan($input, 3, []);
		$this->assertEquals([], $expected);
		$this->assertEquals("def", $token->content);
		$this->assertEquals(3, $token->start);
		$this->assertEquals(6, $token->end);
	}



	function testUntil_5()
	{
		$input = "abcdef\nqwerty\nuioúp";
		$parser = new Until('value', [
			'~[0-9]+~',
		]);

		list($token, $expected) = $parser->scan($input, 3, []);
		$this->assertEquals([], $expected);
		$this->assertEquals("def\nqwerty\nuioúp", $token->content);
		$this->assertEquals(3, $token->start);
		$this->assertEquals(20, $token->end);
	}

}
