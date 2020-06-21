<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit\Framework\TestCase;
use LogicException;


class SequenceTest extends TestCase
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



	function testSample3()
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
		$this->assertEquals([], $expected);
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
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertSame('col1 != :param1 OR col2 = :param2', (string) $ast);
		$this->assertEquals([], $expected);
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
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertSame('(col1 != :param1 OR col2 = :param2)', (string) $ast);
		$this->assertEquals([], $expected);
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
	 * @dataProvider dataUnmatch
	 */
	function testUnmatch($src, $expected)
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



	function dataUnmatch()
	{
		return [
			['col != param', ['param' => 7]],
			['col x= param', ['op' => 4]],
			[':col x= param', ['col' => 0]],
		];
	}



	/**
	 * @dataProvider dataUnmatch
	 */
	function testUnmatch2($src, $expected)
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
	 * @dataProvider dataUnmatch2
	 */
	function testUnmatch3($src, $expected)
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



	function dataUnmatch2()
	{
		return [
			['col != param', ['param' => 7]],
			['col x= param', ['op !=' => 4, 'symbol-like' => 4, 'symbol-ilike' => 4, 'symbol-match' => 4]],
			[':col x= param', ['col' => 0]],
		];
	}



	function testUnmatch4()
	{
		$src = "name = [1, 2, 3, x, 4, 8]";

		$sep = new Whitechars(Null, False);
		$parser = new Sequence(Null, [
			new Pattern('col', ['~[a-z]+~']),
			$sep,
			new OneOf(Null, [
				new Match('op =', ['=']),
				new Match('op !=', ['!=']),
				new Match('symbol-like', ['LIKE']),
				new Match('symbol-ilike', ['ILIKE']),
				new Match('symbol-match', ['MATCH']),
			]),
			$sep,
			new Match('start-bracket', ['[']),
			new Variants(Null, [
				new Numeric('vals'),
				new Pattern(Null, ['~\s*,\s*~']),
			]),
			new Match('end-bracket', [']']),
		]);
		list($token, $expec) = $parser->scan($src, 0, []);
		$this->assertFalse($token);
		$this->assertSame([
			'vals' => 17,
			'end-bracket' => 17,
		], $expec);
	}



	function testFail4()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Sequence combinator must containt minimal two items.");
		new Sequence(Null, []);
	}



	function testFail5()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Sequence combinator must containt minimal two items.");
		new Sequence(Null, [new Match(Null, [])]);
	}

}
