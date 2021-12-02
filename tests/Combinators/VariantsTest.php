<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit\Framework\TestCase;
use LogicException;


class VariantsTest extends TestCase
{


	function testPartially1()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $sep], [$num], [$num]);
		$src = '1 2 3 xsf';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertSame('1 2 3', (string) $ast);
		$this->assertEquals(0, $ast->start);
		$this->assertEquals(5, $ast->end);
		$this->assertCount(5, $ast->content);
		$this->assertEquals(['num' => 6], $expected);
	}



	function testPartially2()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $sep], [$num]);
		$src = '1 2 3 xsf';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertSame('1 2 3 ', (string) $ast);
		$this->assertEquals(0, $ast->start);
		$this->assertEquals(6, $ast->end);
		$this->assertCount(6, $ast->content);
		$this->assertSame(['num' => 6], $expected);
	}



	function testPartially3()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null, False);
		$parser = new Variants('foo', [$num, $sep], [$num]);
		$src = '1 2 3 xsf';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertSame('1 2 3', (string) $ast);
		$this->assertEquals(0, $ast->start);
		$this->assertEquals(6, $ast->end);
		$this->assertCount(3, $ast->content);
		$this->assertSame(['num' => 6], $expected);
	}



	function testPartially4()
	{
		$num = new Numeric('num');
		$parenthesis = new Pattern('symbol', [
			'~\(~',
			'~\)~',
		]);
		$sep = new Whitechars(Null, False);
		$parser = new Variants('foo', [$num, $sep, $parenthesis], [$num]);
		$src = '1 2 3 (xsf';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals(['num' => 7], $expected);
		$this->assertSame('1 2 3 (', (string) $ast);
	}



	/**
	 * žádné matchnutí = [false, [$name]]
	 */
	function testNoMatch_1()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $sep], [$num], [$num]);
		$src = 'x 2 3 xsf';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals(['num' => 0], $expected);
		$this->assertFalse($token);
	}



	function testNoMatch_withOneOf()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null);
		$symbol = new OneOf('symbol', [
			new Match('symbol-like', ['LIKE']),
			new Match('symbol-like', ['ILIKE']),
			new Match('symbol-match', ['MATCH']),
		]);
		$parser = new Variants('foo', [$num, $sep, $symbol], [$num, $symbol], [$num, $symbol]);
		$src = 'x 2 3 xsf';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals(['num' => 0, 'symbol-like' => 0, 'symbol-match' => 0, ], $expected);
		$this->assertFalse($token);
	}



	/**
	 * úspěšné matchnutí, ale ještě nejsme na konci = [Token, [$name]]
	 */
	function testMatchButNoAll()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $sep], [$num], [$num]);
		$src = '1 2 3 xsf';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals(['num' => 6], $expected);
		$this->assertEquals(5, count($token->content));
		$this->assertEquals('1 2 3', (string) $token);
	}



	function testMatchButWithGarbage()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $sep], [$num], [$num]);
		$src = '1 2 3 ';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertEquals(5, count($token->content));
		$this->assertEquals('1 2 3', (string) $token);
		$this->assertEquals(0, $token->start);
		$this->assertEquals(5, $token->end);
	}



	function testMatchButNoAll3()
	{
		$num = new Numeric('num');
		$symbol = new Match('symbol', ['@']);
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $symbol, $sep], [$num, $symbol], [$num, $symbol]);
		$src = '1 2 3 @ a b';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals(['num' => 8, 'symbol' => 8, ], $expected);
		$this->assertEquals(7, count($token->content));
		$this->assertEquals('1 2 3 @', (string) $token);
		$this->assertEquals(['num' => 8, 'symbol' => 8], $expected);
	}



	function testMatchButNoAll4()
	{
		$num = new Numeric('num');
		$symbol = new OneOf('symbol', [
			new Match('symbol-like', ['LIKE']),
			new Match('symbol-like', ['ILIKE']),
			new Match('symbol-match', ['MATCH']),
		]);
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $symbol, $sep], [$num, $symbol], [$num, $symbol]);
		$src = '1 2 3 @ a b';
		list($token, $expected) = $parser->scan($src, 0, []);
		//~ $this->assertEquals(['num' => 6, 'symbol' => 6, ], $expected);
		$this->assertEquals(5, count($token->content));
		$this->assertEquals('1 2 3', (string) $token);
		$this->assertEquals(['num' => 6, 'symbol-match' => 6, 'symbol-like' => 6], $expected);
	}



	function testMatchButNoAll5()
	{
		$num = new Numeric('num');
		$symbol = new OneOf(Null, [
			new Match('symbol-like', ['LIKE']),
			new Match('symbol-like', ['ILIKE']),
			new Match('symbol-match', ['MATCH']),
		]);
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $symbol, $sep], [$num, $symbol], [$num, $symbol]);
		$src = '1 2 3 @ a b';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals(['num' => 6, 'symbol-like' => 6, 'symbol-match' => 6, ], $expected);
		$this->assertEquals(5, count($token->content));
		$this->assertEquals('1 2 3', (string) $token);
		$this->assertEquals(['num' => 6, 'symbol-like' => 6, 'symbol-match' => 6], $expected);
	}



	/**
	 * úspěšné matchnutí, a jsme na konci = [Token, []]
	 */
	function testMatchAndAll()
	{
		$num = new Numeric('num');
		$sep = new Whitechars(Null);
		$parser = new Variants('foo', [$num, $sep], [$num], [$num]);
		$src = '1 2 3';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertEquals(5, count($token->content));
		$this->assertEquals('1 2 3', (string) $token);
		$this->assertEquals(0, $token->start);
		$this->assertEquals(5, $token->end);
	}



	function testFail1()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Variants combinator must containt minimal two variant.");
		new Variants(Null, []);
	}



	function testFail2()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Variants combinator must containt minimal two variant.");
		new Variants(Null, [new Numeric('num')]);
	}

}
