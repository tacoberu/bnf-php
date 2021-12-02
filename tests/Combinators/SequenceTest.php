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



	function testSample7()
	{
		$sep = new Whitechars(Null, False);
		$identifier = new Pattern('Identifier', ['~[a-zA-Z0-9]+~']);
		$parser = new Sequence('Element', [
			$identifier,
			(new Sequence(Null, [$sep, new Text('Name')]))->setOptional(),
		]);

		$src = 'Token "ahoj"';
		list($ast,) = $parser->scan($src, 0, []);
		$this->assertSame('Element', $ast->getName());
		$this->assertSame('Token "ahoj"', (string) $ast);

		$src = 'Token';
		list($ast,) = $parser->scan($src, 0, []);
		$this->assertSame('Element', $ast->getName());
		$this->assertSame('Token', (string) $ast);
	}



	function testSample8()
	{
		$sep = new Whitechars(Null, False);
		$identifier = new Pattern('Identifier', ['~[a-zA-Z0-9]+~']);
		$element = new Sequence('Element', [
			$identifier,
			(new Sequence(Null, [$sep, new Text('Name')]))->setOptional(),
		]);
		$parser = new Sequence('Collection', [
			new Match('CollectionStart', ['['], False),
			new Variants(Null, [
				$element,
				$sep
			]),
			new Match('CollectionEnd', [']'], False),
		]);

		$src = '[Token "ahoj"]';
		list($ast,) = $parser->scan($src, 0, []);
//~ dump($ast->content[0]);
		$this->assertSame('Collection', $ast->getName());
		$this->assertSame(1, count($ast->content));
		$this->assertSame(1, count($ast->content[0]->content));
		$node = $ast->content[0]->content[0];
//~ dump($node);
		$this->assertSame('Element', $node->getName());
		$this->assertSame('Token "ahoj"', (string) $node);

		$src = '[Token]';
		list($ast,) = $parser->scan($src, 0, []);
//~ dump($ast->content[0]);
		$this->assertSame('Collection', $ast->getName());
		$this->assertSame(1, count($ast->content));
		$this->assertSame(1, count($ast->content[0]->content));
		$node = $ast->content[0]->content[0];
//~ dump($node);
		$this->assertSame('Element', $node->getName());
		$this->assertSame('Token', (string) $node);
	}



	function testSample9()
	{
		$sep = new Whitechars(Null, False);

		$parser = (new Sequence(Null, [$sep, new Text('Name')]));

		$src = ' "Token ahoj"';
		list($ast,) = $parser->scan($src, 0, []);
		$this->assertSame('Name', $ast->getName());
	}



	function _testSample10()
	{
		$sep = new Whitechars(Null, False);
		$parser = (new Sequence(Null, [
			$sep,
			new Text('Name'),
			$sep,
			new Match(Null, ['x']),
		]));

		$src = ' "Token ahoj"' . "\n\n\nx";
		list($ast, $expec) = $parser->scan($src, 0, []);
		dump($expec);
		dump($ast);
		$this->assertSame('Name', $ast->getName());
		dump(strlen($src));
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



/*
	function _testBug1()
	{
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~[\r\n]+~',], False);
		$comment = new Pattern('Comment', ['~^#.*$~m'], False);
		$identifier = new Pattern('Identifier', ['~' . self::$symbolPattern . '~']);
		$textElement = new Pattern('TextElement', ['~[^\{\}]+~s']);
		$pattern = new Sequence('VariableReference', [
			new Pattern(Null, ['~\{[ \t]*~'], False),
			new OneOf('Pattern', [
				new Pattern('VariableReference', ['~' . self::$symbolPattern . '~']),
				new Text('StringLiteral'),
				new Sequence('FunctionReference', [
					new Pattern('Id', ['~[A-Z]+~']),
					new Match(Null, ['('], False),
					new Variants('Arguments', [
						new OneOf(Null, [
							new Sequence('NamedArgument', [
								new Pattern('Name', ['~[a-z][a-zA-Z]*~']),
								new Pattern(Null, ['~\s*\:\s*~'], False),
								new OneOf('Value', [
									new Numeric('NumericLiteral'),
									new Text('StringLiteral'),
								]),
							]),
							$identifier,
						]),
						new Pattern(Null, ['~\s*,\s*~'], False),
					]),
					new Match(Null, [')'], False),
				]),
			]),
			new Pattern(Null, ['~[ \t]*\}~'], False),
		]);
		$pattern = new Variants('Pattern', [
			$nl,
			$pattern,
			new FluentSelectExpression('SelectExpression'),
			$textElement,
		]);

		$message = new Sequence('Message', [
			$identifier,
			$sep,
			new Match('assign', ['='], False),
			(new Pattern(Null, ['~[ \t]+~'], False))->setOptional(),
			new Indent(Null, $pattern, self::$skipIndent),
		]);

		$this->schema = new Variants('Resource', [
			$message,
			$comment,
			$nl,
		]);
	}
	*/

}
