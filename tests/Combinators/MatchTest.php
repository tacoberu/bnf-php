<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit\Framework\TestCase;
use LogicException;


class MatchTest extends TestCase
{


	function testName()
	{
		$patterns = [
			'a',
			'b',
		];
		$this->assertSame('num', (new Match('num', $patterns))->getName());
		$this->assertSame('xum', (new Match('xum', $patterns))->getName());
	}



	function testCapture()
	{
		$patterns = [
			'a',
			'b',
		];
		$this->assertTrue((new Match('num', $patterns))->isCapture());
		$this->assertFalse((new Match('num', $patterns, False))->isCapture());
	}



	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($def, $src, $offset, $content)
	{
		$parser = new Match(null, $def);
		list($token,) = $parser->scan($src, $offset, []);
		$this->assertToken($content, $token);
	}



	function dataCorrect()
	{
		$def1 = [
			'x',
			'234',
		];
		$def2 = [
			': ',
			':',
		];
		return [
			[$def1, 'x1234 5 x 6', 2, '234'],
			[$def1, 'x1234 5 x 6', 0, 'x'],
			[$def1, 'x1234 5 x 6', 8, 'x'],
			[$def1, 'x1234 5 X 6', 8, 'X'],
			[$def2, '"abc": "def"', 5, ': '],
		];
	}



	/**
	 * @dataProvider dataFalse
	 */
	function testFalse($src, $offset)
	{
		$parser = new Match('num', [
			'a',
			'b',
		]);
		list($token, ) = $parser->scan($src, $offset, []);
		$this->assertFalse($token);
	}



	function dataFalse()
	{
		return [
			['x1234 5 x 6', 0],
			['x1234 5 x 6', 5],
			['5.6', 5],
		];
	}



	private function assertToken($content, Token $token)
	{
		$this->assertSame($content, $token->content);
	}

}
