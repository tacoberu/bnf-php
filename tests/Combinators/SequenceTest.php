<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Parser;
use PHPUnit\Framework\TestCase;
use LogicException;


class SequenceTest extends TestCase
{

	function testSequenceOfOnePartIsFail()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Sequence combinator must containt minimal two items.");
		new Sequence('foo', [
			new Pattern('col', ['~[a-z0-9]+~']),
		]);
	}



	/**
	 * @dataProvider dataTwoParts
	 */
	function testTwoParts(string $src, string $serialize, int $offset, int $start, int $end)
	{
		$parser = self::bnf('pair');
		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertEquals([], $expected);
		$this->assertTokenComposit($start, $end, 2, Sequence::class, $ast);
		$this->assertSame($serialize, (string) $ast);
		$this->assertSame('pair', $ast->getName());
	}



	function dataTwoParts()
	{
		return [
			['col123', 'col123', 0, 0, 6],
			['col1', 'col1', 0, 0, 4],
			['col1', 'l1', 2, 2, 4],
			['col1x', 'col1', 0, 0, 4],
			['col1x1', 'col1', 0, 0, 4],
			['col1x1 ', 'col1', 0, 0, 4],
		];
	}



	/**
	 * @dataProvider dataSequenceOfTwoParts_Fail
	 */
	function testSequenceOfTwoParts_Fail(string $src, int $offset, array $expect)
	{
		$parser = self::bnf('pair');
		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertFalse($ast);
		$this->assertEquals($expect, $expected);
	}



	function dataSequenceOfTwoParts_Fail()
	{
		return [
			['col', 0, ['b' => 3]],
			['col 123', 0, ['b' => 3]],
			['c', 0, ['b' => 1]],
			['c', 1, ['a' => 1]],
			['c', 2, ['a' => 2]], // OutOfRange
			['', 2, ['a' => 2]], // OutOfRange
			['', 0, ['a' => 0]],
		];
	}



	/**
	 * @dataProvider dataSequenceOfTwoPartsWithOptional
	 */
	function testSequenceOfTwoPartsWithOptional(string $src, string $serialize, int $offset, int $start, int $end, int $count)
	{
		$parser = self::bnf('pair opt');
		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertEquals([], $expected);
		$this->assertTokenComposit($start, $end, $count, Sequence::class, $ast);
		$this->assertSame($serialize, (string) $ast);
		$this->assertSame('pair', $ast->getName());
	}



	function dataSequenceOfTwoPartsWithOptional()
	{
		return [
			['col123', 'col123', 0, 0, 6, 2],
			['col 123', 'col', 0, 0, 3, 1],
			['col1', 'col1', 0, 0, 4, 2],
			['col1', 'l1', 2, 2, 4, 2],
		];
	}



	/**
	 * @dataProvider dataThreeParts
	 */
	function testThreeParts(string $src, string $serialize, int $offset, int $start, int $end, int $count)
	{
		$parser = self::bnf('seq3 with sep');
		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertEquals([], $expected);
		$this->assertTokenComposit($start, $end, $count, Sequence::class, $ast);
		$this->assertSame($serialize, (string) $ast);
		$this->assertSame('foo', $ast->getName());
	}




	function dataThreeParts()
	{
		return [
			['col != :param', 'col != :param', 0, 0, 13, 3],
			['col != :param', 'l != :param', 2, 2, 13, 3],
		];
	}



	/**
	 * @dataProvider dataThreeParts_Fail
	 */
	function testThreeParts_Fail(string $src, int $offset, array $expect)
	{
		$parser = self::bnf('seq3 with sep');
		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertFalse($ast);
		$this->assertEquals($expect, $expected);
	}



	function dataThreeParts_Fail()
	{
		return [
			['', 0, ['col' => 0]],
			['a', 0, ['foo' => 1]],
			['col ', 0, ['op' => 4]],
			['col != param', 0, ['param' => 7]],
			['col x= param', 0, ['op' => 4]],
			[':col x= param', 0, ['col' => 0]],
			['col != param', 0, ['param' => 7]],
		];
	}



	function testSequenceInVariants()
	{
		$expr = self::bnf('seq3 with sep');
		$parser = new Variants(Null, [
			$expr,
			new Pattern('bool', ['~\s+AND\s+~', '~\s+OR\s+~']),
		]);
		$src = 'col1 != :param1 OR col2 = :param2';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertSame($src, (string) $ast);
		$this->assertTokenComposit(0, 33, 3, Variants::class, $ast);
	}



	function testThreeAndBoolInVariants()
	{
		$expr = self::bnf('seq3 with sep');
		$bool = new Pattern('bool', ['~\s+AND\s+~', '~\s+OR\s+~']);

		$parser = new Variants(Null, [
			$expr,
			$bool,
		], [$expr], [$expr]);
		$src = 'col1 != :param1 OR col2 = :param2 AND ';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertSame('col1 != :param1 OR col2 = :param2', (string) $ast);
		$this->assertTokenComposit(0, 33, 3, Variants::class, $ast);
	}



	function testThreeAndBoolInVariantsAsSubExpr()
	{
		$expr = self::bnf('seq3 with sep');
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
		$this->assertEquals([], $expected);
		$this->assertSame('(col1 != :param1 OR col2 = :param2)', (string) $ast);
		$this->assertTokenComposit(0, 35, 1, Variants::class, $ast);
	}



	function testThreeAndBoolInVariantsAsSubExpr_2()
	{
		$expr = self::bnf('seq3 with sep');
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
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertSame('(col1 != :param1 OR col2 = :param2) col3 == :par1', (string) $ast);
		$this->assertTokenComposit(0, 49, 2, Variants::class, $ast);
	}



	function testThreeWithOptional()
	{
		$parser = self::bnf('seq3 with opt');

		$src = 'Token "ahoj"';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertSame('Element', $ast->getName());
		$this->assertTokenComposit(0, 12, 2, Sequence::class, $ast);
		$this->assertSame('Token "ahoj"', (string) $ast);

		$src = 'Token';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertSame('Element', $ast->getName());
		$this->assertSame('Token', (string) $ast);
		$this->assertTokenComposit(0, 5, 1, Sequence::class, $ast);
	}



	/**
	 * @dataProvider dataCollection
	 */
	function testCollection(string $src, string $serialize, int $offset, int $start, int $end, int $count, string $op)
	{
		$parser = self::bnf('collection');

		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertSame([], $expected);
		$this->assertTokenComposit($start, $end, $count, Sequence::class, $ast);
		$this->assertSame($serialize, (string) $ast);
		$this->assertCount(1, $ast->content);
		$this->assertCount(1, $ast->content[0]->content);
		//~ $this->assertCount(2, $ast->content[0]->content[0]->content);

		$this->assertSame('Collection', $ast->getName());
		$this->assertNull($ast->content[0]->getName());
		$this->assertSame('Element', $ast->content[0]->content[0]->getName());
		$this->assertSame('Identifier', $ast->content[0]->content[0]->content[0]->getName());
		//~ $this->assertSame('Name', $ast->content[0]->content[0]->content[1]->getName());
	}



	function dataCollection()
	{
		return [
			['[Token "ahoj"]', ' Token "ahoj" ', 0, 0, 14, 1, 'op !='],
			['[Token]', ' Token ', 0, 0, 7, 1, 'op !='],
		];
	}



	function testSequenceWithTextAndSep()
	{
		$parser = self::bnf('one with seps');
		$src = ' "Token ahoj"';
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertEquals([], $expected);
		$this->assertNull($ast->getName());
		$this->assertSame('Name', $ast->content[0]->getName());
		//~ $this->assertTokenText(1, 13, '"Token ahoj"', $ast);
		$this->assertTokenComposit(0, 13, 1, Sequence::class, $ast);
		$this->assertTokenText(1, 13, '"Token ahoj"', $ast->content[0]);
		$this->assertSame($src, (string) $ast);
	}



	function testSequenceOfTwoWithSeps()
	{
		$parser = self::bnf('two with seps');
		$src = ' "Token ahoj"' . "\n\n\nx";
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertSame([], $expected);
		$this->assertNull($ast->getName());
		$this->assertSame(['Name'], $ast->type->getExpectedNames());
		$this->assertCount(2, $ast->content);
		$this->assertTokenComposit(0, 17, 2, Sequence::class, $ast);
		$this->assertSame(' "Token ahoj"   x', (string) $ast);
	}



	/**
	 * @dataProvider dataThreeWithOneOf
	 */
	function testThreeWithOneOf(string $src, string $serialize, int $offset, int $start, int $end, int $count, string $op)
	{
		$parser = self::bnf('three, with oneof');
		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertSame([], $expected);
		$this->assertTokenComposit($start, $end, $count, Sequence::class, $ast);
		$this->assertSame($serialize, (string) $ast);
		$this->assertNull($ast->getName());
		$this->assertSame('col', $ast->content[0]->getName());
		$this->assertSame($op, $ast->content[1]->getName());
		$this->assertSame('param', $ast->content[2]->getName());
	}



	function dataThreeWithOneOf()
	{
		return [
			['a != :p', 'a != :p', 0, 0, 7, 3, 'op !='],
			['abc != :pcd', 'abc != :pcd', 0, 0, 11, 3, 'op !='],
			['abc LIKE :pcd', 'abc LIKE :pcd', 0, 0, 13, 3, 'symbol-like'],
			['abc ILIKE :pcd', 'abc ILIKE :pcd', 0, 0, 14, 3, 'symbol-ilike'],
			['abc MATCH :pcd', 'abc MATCH :pcd', 0, 0, 14, 3, 'symbol-match'],
		];
	}



	/**
	 * @dataProvider dataThreeWithOneOf_Unmatch
	 */
	function testThreeWithOneOf_Unmatch($src, $expec)
	{
		$parser = self::bnf('three, with oneof');
		list($ast, $expected) = $parser->scan($src, 0, []);
		$this->assertFalse($ast);
		$this->assertSame($expected, $expec);
	}



	function dataThreeWithOneOf_Unmatch()
	{
		return [
			['', ['col' => 0]],
			['a', []], // ?  @FIXME Mělo by to očekávat 'op'.
			['a ', ['op !=' => 2, 'symbol-like' => 2, 'symbol-ilike' => 2, 'symbol-match' => 2]],
			['a !=', []], // ?  @FIXME Mělo by to očekávat 'op'.
			['col != param', ['param' => 7]],
			['col x= param', ['op !=' => 4, 'symbol-like' => 4, 'symbol-ilike' => 4, 'symbol-match' => 4]],
			[':col x= param', ['col' => 0]],
		];
	}



	/**
	 * @dataProvider dataThreeWithOneOfAndBracked
	 */
	function testThreeWithOneOfAndBracked(string $src, string $serialize, int $offset, int $start, int $end, int $count)
	{
		$parser = self::bnf('three, with oneof and bracket');
		list($ast, $expected) = $parser->scan($src, $offset, []);
		$this->assertSame([], $expected);
		$this->assertTokenComposit($start, $end, $count, Sequence::class, $ast);
		$this->assertSame($serialize, (string) $ast);
	}



	function dataThreeWithOneOfAndBracked()
	{
		return [
			['a != [5]', 'a != [5]', 0, 0, 8, 5],
			['name = [1, 2, 3, 4, 8]', 'name = [1, 2, 3, 4, 8]', 0, 0, 22, 5],
			['name = [1, 2, 3, 4, 8]', 'e = [1, 2, 3, 4, 8]', 3, 3, 22, 5],
		];
	}



	/**
	 * @dataProvider dataThreeWithOneOfAndBracked_Unmatch
	 */
	function testThreeWithOneOfAndBracked_Unmatch(string $src, array $expec)
	{
		$parser = self::bnf('three, with oneof and bracket');
		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertFalse($token);
		$this->assertSame($expec, $expected);
	}



	function dataThreeWithOneOfAndBracked_Unmatch()
	{
		return [
			["name = [1, 2, 3, x, 4, 8]", [
				'vals' => 17,
				'end-bracket' => 17,
			]],
		];
	}



	function testSequenceWithoutChildrensIsFail()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Sequence combinator must containt minimal two items.");
		new Sequence(Null, []);
	}



	function testSequenceWithOneItemIsFail()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Sequence combinator must containt minimal two items.");
		new Sequence(Null, [new Match(Null, [])]);
	}



	function _testSequenceWithTwoNonamedItemIsFail()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage("Sequence combinator must have a name, or its elements must have a name.");
		new Sequence(Null, [
			new Match(Null, []),
			new Match(Null, []),
			]);
	}



	function _testSequence_1()
	{
		$sep = new Whitechars(Null, False);
		$identifier = new Pattern('Identifier', ['~[a-zA-Z0-9]+~']);
		$parser = new Sequence(Null, [
			$sep,
			$identifier,
			//~ new Text('Name'),
		]);
		$src = ' "Token ahoj"';
		$src = ' token ahoj"';
		list($ast, $expected) = $parser->scan($src, 0, []);
		dump($ast, $expected);

die("\n------\n" . __file__ . ':' . __line__ . "\n");
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



	static private function bnf(string $name)
	{
		$sep = new Whitechars(Null, False);
		$identifier = new Pattern('Identifier', ['~[a-zA-Z0-9]+~']);
		switch ($name) {
			case 'one with seps':
				return (new Sequence(Null, [
					$sep, new Text('Name')
				]));

			case 'two with seps':
				return (new Sequence(Null, [
					$sep,
					new Text('Name'),
					$sep,
					new Match(Null, ['x']),
				]));

			case 'pair':
				return new Sequence('pair', [
					new Pattern('a', ['~[a-zA-Z]+~']),
					(new Pattern('b', ['~[0-9]+~'])),
				]);

			case 'pair opt':
				return new Sequence('pair', [
					new Pattern('a', ['~[a-zA-Z]+~']),
					(new Pattern('b', ['~[0-9]+~']))->setOptional(),
				]);

			case 'seq3 with sep':
				return new Sequence('foo', [
					new Pattern('col', ['~[a-z0-9]+~']),
					$sep,
					new Pattern('op', ['~[\!\=]+~']),
					$sep,
					new Pattern('param', ['~\:[a-z0-9]+~']),
				]);

			case 'seq3 with opt':
				return new Sequence('Element', [
					$identifier,
					(new Sequence(Null, [$sep, new Text('Name')]))->setOptional(),
				]);

			case 'seq3 with opt 2':
				return new Sequence('Element', [
					$identifier,
					(new Sequence(Null, [
						$sep,
						new Pattern('Name', ['~[0-9]+~']),
						]))->setOptional(),
				]);

			case 'collection':
				$element = self::bnf('seq3 with opt');
				return new Sequence('Collection', [
					new Match('CollectionStart', ['['], False),
					new Variants(Null, [
						$element,
						$sep
					]),
					new Match('CollectionEnd', [']'], False),
				]);

			case 'three, with oneof':
				return new Sequence(Null, [
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

			case 'three, with oneof and bracket':
				return new Sequence(Null, [
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

			default:
				throw new \LogicException("Unknows name of BNF: '$name'.");
		}
	}



	private function assertTokenText(int $start, int $end, string $content, Token $ast)
	{
		$this->assertInstanceOf(Text::class, $ast->type);
		$this->assertSame($content, $ast->content);
		$this->assertSame($start, $ast->start);
		$this->assertSame($end, $ast->end);
	}



	private function assertTokenComposit(int $start, int $end, int $contentCount, string $type, Token $ast)
	{
		$this->assertInstanceOf($type, $ast->type);
		$this->assertCount($contentCount, $ast->content, "Count of content.");
		$this->assertSame($start, $ast->start, "Start of range.");
		$this->assertSame($end, $ast->end, "End of range.");
	}



	private static function formatExpected(string $src, int $offset, array $expected)
	{
		$e = Parser::fail($src, $offset, array_keys($expected));
		return $e->getMessage() . "\n"
			 . $e->getContextSource() . "\n"
			 . "\n";
	}

}
