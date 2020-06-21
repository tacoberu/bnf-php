<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit\Framework\TestCase;
use LogicException;


class WhitecharsTest extends TestCase
{


	/**
	 * @dataProvider dataWhitechars
	 */
	function testSampleWhitechars($src, $offset, $content)
	{
		$parser = new Whitechars('foo');
		list($token) = $parser->scan($src, $offset, []);
		$this->assertToken($content, $token);
	}



	function dataWhitechars()
	{
		return [
			[' ', 0, ' '],
			['  ', 0, '  '],
			['  ', 1, ' '],
			["\t", 0, "\t"],
			["\t ", 0, "\t "],
			["\tx", 0, "\t"],
			["\nx", 0, "\n"],
		];
	}



	/**
	 * @dataProvider dataWhitecharsFalse
	 */
	function testSampleWhitecharsFalse($src, $offset)
	{
		$parser = new Whitechars('foo');
		list($token) = $parser->scan($src, $offset, []);
		$this->assertFalse($token);
	}



	function dataWhitecharsFalse()
	{
		return [
			['x1234 5 x 6', 0],
			['x1234 5 x 6', 3],
			['5.6', 5],
			['5.6', 1],
			['.6', 0],
		];
	}



	private function assertToken($content, Token $token)
	{
		$this->assertSame($content, $token->content);
	}

}
