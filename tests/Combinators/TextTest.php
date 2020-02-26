<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit_Framework_TestCase;
use LogicException;


class TextTest extends PHPUnit_Framework_TestCase
{

	function testName()
	{
		$this->assertSame('num', (new Text('num'))->getName());
		$this->assertSame('xum', (new Text('xum'))->getName());
	}



	function testCapture()
	{
		$this->assertTrue((new Text('num'))->isCapture());
		$this->assertFalse((new Text('num', False))->isCapture());
	}



	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($src, $offset, $content)
	{
		$parser = new Text('foo');
		list($token) = $parser->scan($src, $offset, []);
		$this->assertToken($content, $token);
	}



	function dataCorrect()
	{
		return [
			['""', 0, '""'],
			['\'\'', 0, "''"],
			['``', 0, "``"],
			['"a"', 0, '"a"'],
			['"a b c"', 0, '"a b c"'],
			['  "a b c"', 2, '"a b c"'],
			['"a "b c"', 0, '"a "'],
			['"a \"b c"', 0, '"a \"b c"'],
			["'a \x5c'b c'", 0, "'a \'b c'"],
			["'a \x5c\x5c'b c'", 0, "'a \\\'"],
		];
	}



	/**
	 * @dataProvider dataFalse
	 */
	function testFalse($src, $offset)
	{
		$parser = new Text('foo');
		list($token) = $parser->scan($src, $offset, []);
		$this->assertFalse($token);
	}



	function dataFalse()
	{
		return [
			['x1234 5 x 6', 0],
			['x1234 5 x 6', 5],
			['5.6', 5],
			['5.6', 1],
			['.6', 0],
			['"abc', 0],
			[' "abc', 0],
			['', 0],
		];
	}



	private function assertToken($content, Token $token)
	{
		$this->assertSame($content, $token->content);
	}

}
