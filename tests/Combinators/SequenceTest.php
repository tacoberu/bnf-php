<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit_Framework_TestCase;
use LogicException;


class SequenceTest extends PHPUnit_Framework_TestCase
{


	function testCorrect()
	{
		$sep = new Whitechars(Null, False);
		$parser = new Sequence('foo', [
			new Pattern('col', ['~[a-z]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z]+~']),
		]);
		$src = 'col != :param';
		list($ast,) = $parser->scan($src, 0, []);
		$this->assertSame('col != :param', (string) $ast);
		$this->assertCount(3, $ast->content);
		$this->assertSame(0, $ast->start);
		$this->assertSame(13, $ast->end);
	}



	function _____testSample3()
	{
		$sep = new Whitechars(Null, False);
		$expr = new Sequence('foo', [
			new Pattern('col', ['~[a-z0-9]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z0-9]+~']),
		]);

		$parser = new Variants(Null, [
			$expr,
			new Pattern('bool', ['~\s+AND\s+~', '~\s+OR\s+~']),
		]);
		$src = 'col1 != :param1 OR col2 = :param2';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertSame($src, (string) $token);
		//~ dump($expected);
		//~ $this->assertEquals([], $expected);
	}




	function testSample4()
	{
		$sep = new Whitechars(Null, False);
		$expr = new Sequence('foo', [
			new Pattern('col', ['~[a-z0-9]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z0-9]+~']),
		]);
		$bool = new Pattern('bool', ['~\s+AND\s+~', '~\s+OR\s+~']);

		$parser = new Variants(Null, [
			$expr,
			$bool,
		], [$expr], [$expr]);
		$src = 'col1 != :param1 OR col2 = :param2 AND ';
		list($ast,) = $parser->scan($src, 0, []);
		$this->assertSame('col1 != :param1 OR col2 = :param2', (string) $ast);
	}



	function testSample5()
	{
		$sep = new Whitechars(Null, False);
		$expr = new Sequence('foo', [
			new Pattern('col', ['~[a-z0-9]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z0-9]+~']),
		]);
		$bool = new Pattern('bool', ['~\s+AND\s+~', '~\s+OR\s+~']);
		$chain = new Variants(Null, [
			$expr,
			$bool,
		], [$expr], [$expr]);

		$parser = new Variants(Null, [
			new Sequence('subexpr', [
				new Pattern(Null, ['~\s*\(\s*~']),
				$chain,
				new Pattern(Null, ['~\s*\)\s*~']),
			]),
			$chain,
		]);
		$src = '(col1 != :param1 OR col2 = :param2)';
		list($ast,) = $parser->scan($src, 0, []);
		$this->assertSame('(col1 != :param1 OR col2 = :param2)', (string) $ast);
	}



	function testSample6()
	{
		$sep = new Whitechars(Null, False);
		$expr = new Sequence('foo', [
			new Pattern('col', ['~[a-z0-9]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z0-9]+~']),
		]);
		$bool = new Pattern('bool', ['~\s+AND\s+~', '~\s+OR\s+~']);
		$chain = new Variants(Null, [
			$expr,
			$bool,
		], [$expr], [$expr]);

		$parser = new Variants(Null, [
			new Sequence('subexpr', [
				new Pattern(Null, ['~\s*\(\s*~']),
				$chain,
				new Pattern(Null, ['~\s*\)\s*~']),
			]),
			$chain,
		]);
		$src = '(col1 != :param1 OR col2 = :param2) col3 == :par1';
		list($ast,) = $parser->scan($src, 0, []);
		$this->assertSame('(col1 != :param1 OR col2 = :param2) col3 == :par1', (string) $ast);
	}



	/**
	 * @dataProvider dataFail
	 */
	function testFail($src, $expected)
	{
		$sep = new Whitechars(Null, False);
		$parser = new Sequence('foo', [
			new Pattern('col', ['~[a-z]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z]+~']),
		]);
		list($token, $expec) = $parser->scan($src, 0, []);
		$this->assertFalse($token);
		$this->assertSame($expected, $expec);
	}



	function dataFail()
	{
		return [
			['col != param', ['param' => 7]],
			['col x= param', ['op' => 4]],
			[':col x= param', ['col' => 0]],
		];
	}



	/**
	 * @dataProvider dataFail
	 */
	function testFail2($src, $expected)
	{
		$sep = new Whitechars(Null, False);
		$parser = new Sequence(Null, [
			new Pattern('col', ['~[a-z]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z]+~']),
		]);
		list($token, $expec) = $parser->scan($src, 0, []);
		$this->assertFalse($token);
		$this->assertSame($expected, $expec);
	}



	/**
	 * @dataProvider dataFail2
	 */
	function testFail3($src, $expected)
	{
		$sep = new Whitechars(Null, False);
		$parser = new Sequence(Null, [
			new Pattern('col', ['~[a-z]+~']),
			$sep,
			new OneOf(Null, [
				new Match('op !=', ['!=']),
				new Match('symbol-like', ['LIKE']),
				new Match('symbol-ilike', ['ILIKE']),
				new Match('symbol-match', ['MATCH']),
			]),
			$sep,
			new Pattern('param', ['~\:[a-z]+~']),
		]);
		list($token, $expec) = $parser->scan($src, 0, []);
		$this->assertFalse($token);
		$this->assertSame($expected, $expec);
	}



	function dataFail2()
	{
		return [
			['col != param', ['param' => 7]],
			['col x= param', ['op !=' => 4, 'symbol-like' => 4, 'symbol-ilike' => 4, 'symbol-match' => 4]],
			[':col x= param', ['col' => 0]],
		];
	}

}
