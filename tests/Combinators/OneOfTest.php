<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Ref;
use Taco\BNF\Token;
use PHPUnit\Framework\TestCase;
use LogicException;


class OneOfTest extends TestCase
{

	function testName_1()
	{
		$parser = new OneOf('foo', [
			new Pattern('param', ['~\:[a-z]+~']),
			new Pattern('col', ['~[a-z]+~']),
			new Numeric(Null),
		]);
		$this->assertSame('foo', $parser->getName());
		$this->assertSame(['foo'], $parser->getExpectedNames());
	}



	function testName_2()
	{
		$parser = new OneOf(Null, [
			new Pattern('param', ['~\:[a-z]+~']),
			new Pattern('col', ['~[a-z]+~']),
			new Numeric(Null),
		]);
		$this->assertNull($parser->getName());
		$this->assertSame(['param', 'col'], $parser->getExpectedNames());
	}



	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($parser, $src, $expec, $offset)
	{
		list($token, $expected) = $parser->scan($src, $offset, []);
		$this->assertSame($expec, (string) $token);
		$this->assertEquals([], $expected);
	}



	function dataCorrect()
	{
		$parser1 = new OneOf('foo', [
			new Pattern('param', ['~\:[a-z]+~']),
			new Pattern('col', ['~[a-z]+~']),
			new Numeric(Null),
		]);
		$sep = new Whitechars(Null, False);
		$expr = new Sequence('foo', [
			new Pattern('col', ['~[a-z]+~']),
			$sep,
			new Pattern('op', ['~[\!\=]+~']),
			$sep,
			new Pattern('param', ['~\:[a-z]+~']),
		]);
		$parser2 = new OneOf('foo', [
			$expr,
			new Numeric('num'),
		]);
		return [
			[$parser1, '123col != :param', '123', 0],
			[$parser1, '123col != :param', 'col', 3],
			[$parser2, '123col != :param', '123', 0],
			[$parser2, '123col != :param', 'col != :param', 3],
		];
	}



	/**
	 * @dataProvider dataFail
	 */
	function testFail($parser, $src)
	{
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertFalse($token);
		$this->assertEquals(['param' => 0, 'col' => 0], $expected);
	}



	function dataFail()
	{
		$parser = new OneOf('foo', [
			new Pattern('param', ['~\:[a-z]+~']),
			new Pattern('col', ['~[a-z]+~']),
			new Numeric(Null),
		]);
		return [
			[$parser, '?123col != :param'],
			[$parser, ''],
			[$parser, ' '],
			[$parser, "\t"],
		];
	}



	function testPartially()
	{
		$sep = new Whitechars(Null, False);
		$expr = new Sequence('expr', [
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
			new OneOf(Null, [
				new Sequence('subexpr', [
					new Pattern(Null, ['~\s*\(\s*~']),
					$chain,
					new Pattern(Null, ['~\s*\)\s*~']),
				]),
				$expr,
			]),
			$bool,
		]);
		$src = '(col1 != :param1 OR col2 = :param2) col3 == :par1';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals(['bool' => 36], $expected);
		$this->assertSame('(col1 != :param1 OR col2 = :param2) ', (string) $token);
	}



	function testRef()
	{
		$expr1 = new Match('anna', ['anna', 'Anna']);
		$expr2 = new Match('dana', ['dana']);
		$key = new Match('ident', ['X']);
		$number = new Pattern('num', ['~[0-9]+~']);

		$parser = new Sequence('seq', [
			$key,
			new OneOf(Null, [
				new Ref('seq'),
				$expr1,
				$expr2,
				$number,
			]),
		]);
		$src = 'X555dana jana anna';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertSame(0, $token->start);
		$this->assertSame(4, $token->end);
		$this->assertSame('X555', (string) $token);

		$src = 'X-555dana jana anna';
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertFalse($token);
		$this->assertEquals(['seq' => 1, 'anna' => 1, 'dana' => 1, 'num' => 1], $expected);
	}

}
