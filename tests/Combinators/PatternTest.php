<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit_Framework_TestCase;
use LogicException;


class PatternTest extends PHPUnit_Framework_TestCase
{


	function testName()
	{
		$patterns = [
			'~[\+\-]?\d+\.\d+~',
			'~[\+\-]?\d+~',
		];
		$this->assertSame('num', (new Pattern('num', $patterns))->getName());
		$this->assertSame('xum', (new Pattern('xum', $patterns))->getName());
	}



	function testCapture()
	{
		$patterns = [
			'~[\+\-]?\d+\.\d+~',
			'~[\+\-]?\d+~',
		];
		$this->assertTrue((new Pattern('num', $patterns))->isCapture());
		$this->assertFalse((new Pattern('num', $patterns, False))->isCapture());
	}



	function _____testSample()
	{
		$patterns = [
			'~[\+\-]?\d+\.\d+~',
			'~[\+\-]?\d+~',
		];
		//~ dump((new Pattern('num', $patterns))->scan('12', 0, []));
	}



	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($src, $offset, $content)
	{
		$parser = new Pattern('num', [
			'~[\+\-]?\d+\.\d+~',
			'~[\+\-]?\d+~',
		]);
		list($to, $expected) = $parser->scan($src, $offset, []);
		$this->assertToken($content, $to);
		$this->assertSame([], $expected);
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
		$parser = new Pattern('num', [
			'~[\+\-]?\d+\.\d+~',
			'~[\+\-]?\d+~',
		]);
		list($to, $expected) = $parser->scan($src, $offset, []);
		$this->assertFalse($to);
		$this->assertSame(['num' => $offset], $expected);
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



	function testOptional()
	{
		$parser = new Pattern('num', [
			'~[\+\-]?\d+\.\d+~',
			'~[\+\-]?\d+~',
		]);
		$this->assertFalse($parser->isOptional());
		$this->assertTrue($parser->setOptional()->isOptional());
		$this->assertFalse($parser->isOptional());
	}



	private function assertToken($content, Token $token)
	{
		$this->assertSame($content, $token->content);
	}

}
