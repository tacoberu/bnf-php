<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use PHPUnit\Framework\TestCase;
use LogicException;
use Taco\BNF\Combinators\Sequence;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Variants;
use Taco\BNF\Combinators\Match;


class TokenTest extends TestCase
{

	function dataToString()
	{
		$fake = new Match(Null, ['a', 'b']);
		$var = new Variants(Null, [$fake, $fake]);
		$seq = new Sequence(Null, [$fake, $fake]);
		$ptn = new Pattern(Null, []);
		return [
			['col  = :prop', new Token($seq, [
					new Token($ptn, 'col', 0, 3),
					new Token($ptn, '=', 5, 6),
					new Token($ptn, ':prop', 7, 10),
				], 0, 10)],
			['col = :prop', new Token($seq, [
					new Token($ptn, 'col', 0, 3),
					new Token($ptn, '=', 4, 5),
					new Token($ptn, ':prop', 6, 10),
				], 0, 10)],
			['col = :prop', new Token($seq, [
					new Token($ptn, 'col', 0, 3),
					new Token($ptn, '=', 4, 7),
					new Token($ptn, ':prop', 8, 10),
				], 0, 10)],
			['aaa bbb ccc', new Token($var, [
					new Token($ptn, 'aaa', 0, 3),
					new Token($ptn, 'bbb', 4, 10),
					new Token($ptn, 'ccc', 11, 15),
				], 0, 15)],
			[' bbb ccc', new Token($var, [
					new Token($ptn, 'bbb', 4, 10),
					new Token($ptn, 'ccc', 11, 15),
				], 3, 15)],
			[' bbb ccc ', new Token($var, [
					new Token($ptn, 'bbb', 4, 10),
					new Token($ptn, 'ccc', 11, 15),
				], 3, 16)],
			[' bbb xyz ', new Token($var, [
					new Token($ptn, 'bbb', 4, 10),
					' xyz',
				], 3, 16)],
			[' bbb xyz  ', new Token($var, [
					new Token($ptn, 'bbb', 4, 10),
					' xyz',
				], 3, 17)],
		];
	}



	/**
	 * @dataProvider dataToString
	 */
	function testToString($expected, $ast)
	{
		$this->assertEquals($expected, (string) $ast);
	}



	function testToStringExmper()
	{
		$foo = new Match('foo', ['a', 'b']);
		$token = new Token($foo, [
			new Token($foo, '"ahoj"', 6, 12),
		], 5, 12);
		$this->assertSame(' "ahoj"', (string) $token);
	}



	function _testIndexByName()
	{
		$foo = new Match('foo', ['a', 'b']);
		$token = new Token($foo, []);
		dump($token['foo']);
	}
}
