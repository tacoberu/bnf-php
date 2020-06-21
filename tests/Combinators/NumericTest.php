<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit\Framework\TestCase;
use LogicException;


class NumericTest extends TestCase
{

	function testName()
	{
		$this->assertSame('num', (new Numeric('num'))->getName());
		$this->assertSame('xum', (new Numeric('xum'))->getName());
	}



	function testCapture()
	{
		$this->assertTrue((new Numeric('num'))->isCapture());
		$this->assertFalse((new Numeric('num', False))->isCapture());
	}



	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($src, $offset, $content)
	{
		$parser = new Numeric('num');
		list($token,) = $parser->scan($src, $offset, []);
		$this->assertToken($content, $token);
	}



	function dataCorrect()
	{
		return [
			['x1234 5 x 6', 2, '234'],
			['x1234 5 x 6', 1, '1234'],
			['x1234 5 x 6', 6, '5'],
			['x1234 5.6', 6, '5.6'],
			['x1234 5.611', 6, '5.611'],
			['x1234 0.611', 6, '0.611'],
			['+0.611', 0, '+0.611'],
			['-0.611', 0, '-0.611'],
			['-0', 0, '-0'],
			['+0', 0, '+0'],
		];
	}



	/**
	 * @dataProvider dataFalse
	 */
	function testFalse($src, $offset)
	{
		$parser = new Numeric('num');
		list($token, $expected) = $parser->scan($src, $offset, []);
		$this->assertFalse($token);
		$this->assertEquals(['num' => $offset], $expected);
	}



	function dataFalse()
	{
		return [
			['x1234 5 x 6', 0],
			['x1234 5 x 6', 5],
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
