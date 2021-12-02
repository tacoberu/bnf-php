<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use PHPUnit\Framework\TestCase;
use LogicException;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Variants;
use Taco\BNF\Combinators\Sequence;


class ParserTest extends TestCase
{

	function testSample1()
	{
		$parser = new Parser([
			new Pattern(Null, ['~\s+~']),
			new Pattern('element', ['~[^\n]+~']),
		]);
		$src = "\n \naaa bbbbb\n\ncccc ddd\nyty";
		$ast = $parser->parse($src);
		$this->assertSame($src, (string) $ast);
	}



	function testSample2()
	{
		$parser = new Parser([
			new Pattern(Null, ['~\s+~'], False),
			new Pattern('element', ['~[^\n]+~']),
		]);
		$src = "aaa bbbbb  cccc ddd yty";
		$ast = $parser->parse($src);
		$this->assertSame($src, (string) $ast);
	}



	function testFail1()
	{
		$parser = new Parser([
			new Pattern(Null, ['~\s+~'], False),
			new Pattern('element', ['~[a-z]+~']),
		]);
		$src = "123456";
		try {
			$parser->parse($src);
			$this->fail('Expected exception.');
		}
		catch (ParseException $e) {
			$this->assertEquals('Unexpected token on line 1, column 1: expected token \'element\'', $e->getMessage());
			$this->assertEquals(1, $e->getContentLine());
			$this->assertEquals(1, $e->getContentColumn());
			$this->assertEquals(['element'], $e->getExpectedTokens());
			$this->assertEquals(' 1 > 123456
  ---^', $e->getContextSource());
		}
	}



	function testFail2()
	{
		$parser = new Parser([
			new Pattern(Null, ['~\s+~'], False),
			new Pattern('element', ['~[a-z]+~']),
		]);
		$src = "abc 123456";
		try {
			$parser->parse($src);
			$this->fail('Expected exception.');
		}
		catch (ParseException $e) {
			$this->assertEquals('Unexpected token on line 1, column 5: expected token \'element\'', $e->getMessage());
			$this->assertEquals(1, $e->getContentLine());
			$this->assertEquals(5, $e->getContentColumn());
			$this->assertEquals(['element'], $e->getExpectedTokens());
			$this->assertEquals(' 1 > abc 123456
      ---^', $e->getContextSource());
		}
	}



	function testFail3()
	{
		$parser = new Parser([
			new Pattern(Null, ['~\s+~'], False),
			new Pattern('element', ['~[a-z]+~']),
		]);
		try {
			$parser->parse('');
			$this->fail('Expected exception.');
		}
		catch (ParseException $e) {
			$this->assertEquals('Empty content.', $e->getMessage());
		}
	}



	function testSample3()
	{
		$parser = new Parser([
			new Pattern(Null, ['~\s+~'], False),
			new Sequence('element', [
				new Pattern('id', ['~[\w\d\-]+~']),
				new Pattern('assing', ['~\s*=\s*~']),
				new Pattern('msg', ['~[^\n]*~']),
			]),
		]);
		$src = "\n\n\n   \n\n
-brand-name = Foo 3000\n\n
welcome = Welcome, {\$name}, to {-brand-name}!\n
\n  \n
\n  \n\n";
$src .= "name = Abc";

		$ast = $parser->parse($src);

		$this->assertCount(3, $ast->content);
		$this->assertSame('-brand-name = Foo 3000', (string) $ast->content[0]);
		$this->assertSame('welcome = Welcome, {$name}, to {-brand-name}!', (string) $ast->content[1]);
		$this->assertSame('name = Abc', (string) $ast->content[2]);
/*
		$this->assertEquals([
			self::makeToken('element', [
				self::makeToken('id', "-brand-name", 9, 20),
				self::makeToken('msg', "Foo 3000", 23, 31),
			], 9, 31),
			self::makeToken('element', [
				self::makeToken('id', "welcome", 34, 41),
				self::makeToken('msg', 'Welcome, {$name}, to {-brand-name}!', 44, 79),
			], 34, 79),
		], $parser->parse($src));
		/*
		$src = "
-brand-name = Foo 3000\n\n
welcome Welcome, {\$name}, to {-brand-name}!\n
\n  \n";

		//~ dump($parser->parse($src));
		*/
	}



	/**
	 * Bez odsazení
	 * /
	function _____testSample3()
	{
		$parser = new Parser([
			Parser::pattern(Null, '\s+', false),
			Parser::sequence('element', [
				Parser::pattern('id', '[\w\d\-]+'),
				Parser::pattern('assing', '\s*=\s*', false),
				Parser::pattern('msg', '~.+(?=\s*\n\w|\s*\n$)~ismU'),
			]),
		]);

		$src = '
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
greet-by-name = Hello, { $name }!
';
		$this->assertEquals([
			self::makeToken('element', [
				self::makeToken('id', '-brand-name', 1, 12),
				self::makeToken('msg', 'Foo 3000', 15, 23),
			], 1, 23),
			self::makeToken('element', [
				self::makeToken('id', 'welcome', 24, 31),
				self::makeToken('msg', 'Welcome, {$name}, to {-brand-name}!', 34, 69),
			], 24, 69),
			self::makeToken('element', [
				self::makeToken('id', 'greet-by-name', 70, 83),
				self::makeToken('msg', 'Hello, { $name }!', 86, 103),
			], 70, 103),
			], $parser->parse($src));
	}



	function _____testSample4()
	{
		$parser = new Parser([
			Parser::pattern(Null, '\s*\n', false),
			Parser::sequence('element', [
				Parser::pattern('id', '[\w\d-]+'),
				Parser::pattern('assign', '\s*=\s*', false),
				Parser::variants('msg', [
					Parser::sequence('placeables', [
						Parser::pattern('{', '{\s*', False),
						Parser::pattern('placeables-content', '[^}]+'),
						Parser::pattern('}', '\s*}', False),
					]),
					Parser::pattern('text', '~.+(?=\{|\s*\n\w\d\-|\s*$)~ismU'),
					//~ Parser::untilPattern('text', [
						//~ Parser::pattern('id', '\n[\w\d-]+\s*\='),
						//~ Parser::pattern('id', '\n#'),
					//~ ]),
				]),
			]),
		]);

		$src = 'abc = def a bab
xyp = Lorem ipsum  {   abc  } dd
';
		//~ print_r($parser->parse($src));die('=====[' . __line__ . '] ' . __file__);
		$this->assertEquals([
			self::makeToken('element', [
				self::makeToken('id', "abc", 0, 3),
				self::makeToken('msg', [
					self::makeToken('text', "def a bab", 6, 15),
				], 6, 15),
			], 0, 15),
			self::makeToken('element', [
				self::makeToken('id', "xyp", 16, 19),
				self::makeToken('msg', [
					self::makeToken('text', "Lorem ipsum  ", 22, 35),
					self::makeToken('placeables', [
						self::makeToken('placeables-content', "abc  ", 39, 44),
					], 35, 45),
					self::makeToken('text', " dd", 45, 48),
				], 22, 48),
			], 16, 48),
		], $parser->parse($src));
	}



	/**
	 * @dataProvider dataSample5
	 * /
	function _____testSample5($src, $expected)
	{
		$parser = new Parser([
			Parser::pattern(Null, '\s*\n', false),
			Parser::sequence('element', [
				Parser::pattern('id', '[\w\d-]+'),
				Parser::pattern('assign', '\s*=\s*', false),
				Parser::variants('msg', [
					Parser::sequence('placeables', [
						Parser::pattern('{', '{\s*', False),
						Parser::oneOf('placeables-content', [
							Parser::pattern('variable-reference', '\$[^}]+'),
							Parser::pattern('term-reference', '\-[^}]+'),
							Parser::pattern('string-literal', '\"[^\"]+\"'),
						]),
						Parser::pattern('}', '\s*}', False),
					]),
					Parser::pattern('text', '~.+(?=\{|\s*\n\w\d\-|\s*$)~ismU'),
				]),
			]),
		]);
		$this->assertEquals([
			self::makeToken('element', [
				self::makeToken('id', "greet-by-name", 0, 13),
				self::makeToken('msg', [
					self::makeToken('text', "Hello, ", 16, 23),
					$expected,
					self::makeToken('text', "!", 33, 34),
				], 16, 34),
			], 0, 34),
		], $parser->parse($src));
	}



	function dataSample5()
	{
		return [
			['greet-by-name = Hello, { $namex }!', self::makeToken('placeables', [
					self::makeToken('placeables-content', '$namex ', 25, 32)
				], 23, 33),],
			['greet-by-name = Hello, {$namexis}!', self::makeToken('placeables', [
					self::makeToken('placeables-content', '$namexis', 24, 32)
				], 23, 33),],
			['greet-by-name = Hello, { -name  }!', self::makeToken('placeables', [
					self::makeToken('placeables-content', '-name  ', 25, 32)
				], 23, 33),],
			['greet-by-name = Hello, {"{"     }!', self::makeToken('placeables', [
					self::makeToken('placeables-content', '"{"', 24, 27)
				], 23, 33),],
		];
	}



	function _____testSample7()
	{
		$parser = new Parser(Parser::sequence(Null, [
			Parser::pattern('column', '[a-z0-9]+'),
			Parser::pattern(Null, '\s+'),
			Parser::oneOf(Null, [ // zde null, sekvence null, to znamená, že se ta sekvence rozpustí.
				Parser::sequence(Null, [
					Parser::oneOf(null, [
						Parser::pattern('op', '='),
						Parser::pattern('op', '!='),
					]),
					Parser::pattern(Null, '\s+'),
					Parser::pattern('param', '\:[a-z0-9]+'),
				]),
				Parser::sequence(Null, [
					Parser::oneOf(null, [
						Parser::pattern('op', '<='),
						Parser::pattern('op', '>='),
					]),
					Parser::pattern(Null, '\s+'),
					Parser::pattern('param', '\:[a-z0-9]+'),
				]),
				Parser::pattern('param', '\:[a-z0-9]+'),
			]),
		]));
		$src = "col1 >= :par1";
		$src = "col1 :par1";
		$src = "col1 != :par1";

		//~ dump($parser->parse($src));

		$this->assertEquals([
			self::makeToken('column', "col1", 0, 4),
			self::makeToken('op', "!=", 5, 7),
			self::makeToken('param', ":par1", 8, 13),
		], $parser->parse($src));
	}



	function _____testSample8()
	{
		$parser = new Parser(Parser::variants(Null, [
			Parser::pattern(Null, '\s+'),
			Parser::untilPattern('element', '\n'),
		]));
		$src = "\n \naaa bbbbb\n\ncccc ddd\nyty";
		//~ dump($parser->parse($src));
		//*
		$this->assertEquals([
			self::makeToken('element', "aaa bbbbb", 3, 12),
			self::makeToken('element', "cccc ddd", 14, 22),
			self::makeToken('element', "yty", 23, 26),
		], $parser->parse($src));
		//* /
	}



	function _____testSample9()
	{
		$parser = new Parser(Parser::variants(Null, [
			Parser::pattern(Null, '\s+'),
			Parser::sequence(Null, [
				Parser::pattern('element', '[^\n]+'),
			]),
		]));
		$src = "\n \naaa bbbbb\n\ncccc ddd\nyty";
		//~ dump($parser->parse($src));
		//*
		$this->assertEquals([
			self::makeToken('element', "aaa bbbbb", 3, 12),
			self::makeToken('element', "cccc ddd", 14, 22),
			self::makeToken('element', "yty", 23, 26),
		], $parser->parse($src));
		//* /
	}



	function _____testSampleA()
	{
		/*
		$parser = new Parser(Parser::variants(Null, [
			Parser::untilPattern('symbol', '["\']', ['skip' => [Parser::pattern(Null, '\\"'), Parser::pattern(Null, '\\\'')],]),
			Parser::sequence('text', [
				Parser::pattern(Null, '"'),
				Parser::untilPattern(Null, '"', ['skip' => Parser::pattern(Null, '\\"'),]),
			]),
			Parser::sequence('text', [
				Parser::pattern(Null, "'"),
				Parser::untilPattern(Null, "'", ['skip' => Parser::pattern(Null, '\\\''),]),
			]),
		]));
		* /

		// nebo?
		// Najde symbol. Ale protože ten sežere celou řádku, hledá se čím jej omezit. Omezí se další variantou - tou, která je nejblíž. Tím se získá tak akorád krátký úsek.
		$parser = new Parser(Parser::variants(Null, [
			Parser::variants('symbol', [ // najde: 'abc "def\"ghi" jkl "mnop" qx""yz'
				Parser::pattern("s1", '\\"', True),
				Parser::pattern("s2", "\\'", True),
				//~ Parser::pattern("s3", '.+', True),
				Parser::untilToNext(), // hledám až do text1, nebo text2
			]),
			Parser::sequence('text', [
				Parser::pattern('text-1', "'", False),
				Parser::variants(Null, [
					Parser::pattern(Null, "\\'", True), // \' je dřív jak ', tak se použije, a má offset za '
					//~ Parser::pattern(Null, '.+', True),
					Parser::untilToNext(), // až do '
				]),
				Parser::pattern(Null, "'", False),
			]),
			Parser::sequence('text', [// najde: '"def\"ghi" jkl "mnop" qx""yz'
				Parser::pattern('text-2', '"', False),
				Parser::variants(Null, [
					Parser::pattern(Null, '\\"', True),
					//~ Parser::pattern(Null, '.+', True),
					Parser::untilToNext(),
				]),
				Parser::pattern(Null, '"', False),
			]),
		]));
		$src = 'abc "def\"ghi" jkl "mnop" qx""yz';
		//~ print_r($parser->parse($src));
		/*
		$this->assertEquals([
			self::makeToken('symbol', "abc", 3, 12),
			self::makeToken('text', 'def"ghi', 14, 22),
			self::makeToken('symbol', "jkl", 23, 26),
			self::makeToken('text', 'mnop', 14, 22),
			self::makeToken('symbol', 'qx', 14, 22),
			self::makeToken('text', '', 14, 22),
			self::makeToken('symbol', 'yz', 14, 22),
		], $parser->parse($src));
		//* /
	}




	function _____testSampleY()
	{
		// ???
		$parser = new Parser([
			Parser::pattern('x1', '[abc]+'), // match
			Parser::pattern('x2', '[bcd]+'),
			Parser::pattern('x3', '[cdef]+'),
			Parser::pattern('x4', '[abcd]+'),// match
		]);
		$src = "abcdef";
		//~ dump($parser->parse($src));
	}






	function _____testSequenceDefFail()
	{
		$this->setExpectedException(LogicException::class);
		Parser::sequence(Null, []);
	}



	/*
	function _____testSequenceFail()
	{
		$this->setExpectedException(LogicException::class, 'Unexpected token on line 1, column 4: expected token');
		$white = Parser::pattern(Null, '\s+', False);
		$def = Parser::sequence(Null, [
			Parser::pattern('column', '[a-z0-9]+'),
			$white,
			Parser::pattern('param', '[a-z0-9]+'),
		]);
		(new Parser($def))->parseRaw('and');
	}
	*/



	/**
	 * @dataProvider dataSequence
	 * /
	function _____testSequence($def, $src, $expected)
	{
		$this->assertEquals($expected, (new Parser($def))->parseRaw($src));
	}



	function dataSequence()
	{
		$def1 = Parser::sequence(Null, [
			Parser::pattern('column', '[a-z0-9]+'),
			Parser::pattern(Null, '\s+', False),
			Parser::oneOf(Null, [
				Parser::pattern('op', '='),
				Parser::pattern('op', '!='),
			]),
			Parser::pattern(Null, '\s+', False),
			Parser::pattern('param', '\:[a-z0-9]+'),
		]);
		$def2 = Parser::sequence(Null, [
			Parser::pattern('column', '[a-z0-9]+'),
			Parser::pattern(Null, '\s+', False),
			Parser::oneOf(Null, [
				Parser::pattern('op =', '='),
				Parser::pattern('op !=', '!='),
			]),
			Parser::pattern(Null, '\s+', False),
			Parser::pattern('param', '\:[a-z0-9]+'),
		]);
		return [
			[$def1, "col1 != :par1", [self::makeToken(Null, [
				self::makeToken('column', "col1", 0, 4),
				self::makeToken('op', "!=", 5, 7),
				self::makeToken('param', ":par1", 8, 13),
				], 0, 13), []]],
			[$def1, "col1\n != \t :par1", [self::makeToken(Null, [
				self::makeToken('column', "col1", 0, 4),
				self::makeToken('op', "!=", 6, 8),
				self::makeToken('param', ":par1", 11, 16),
				], 0, 16), []]],
			[$def1, "col1 = :par1", [self::makeToken(Null, [
				self::makeToken('column', "col1", 0, 4),
				self::makeToken('op', "=", 5, 6),
				self::makeToken('param', ":par1", 7, 12),
				], 0, 12), []]],
			[$def1, "a = :b", [self::makeToken(Null, [
				self::makeToken('column', "a", 0, 1),
				self::makeToken('op', "=", 2, 3),
				self::makeToken('param', ":b", 4, 6),
				], 0, 6), []]],
			[$def1, "col1 = :par1 a b c", [self::makeToken(Null, [
				self::makeToken('column', "col1", 0, 4),
				self::makeToken('op', "=", 5, 6),
				self::makeToken('param', ":par1", 7, 12),
				], 0, 12), []]],
			[$def1, "col1 = :par1 ", [self::makeToken(Null, [
				self::makeToken('column', "col1", 0, 4),
				self::makeToken('op', "=", 5, 6),
				self::makeToken('param', ":par1", 7, 12),
				], 0, 12), []]],
			[$def1, "col1 = ", [False, ['param']]],
			[$def1, "col1 =", [False, ['param']]],
			[$def1, "col1 ", [False, ['op']]],
			[$def1, "col1", [False, ['op']]],
			[$def2, "col1", [False, ['op =', 'op !=']]],
			[$def2, "col1 ", [False, ['op =', 'op !=']]],
			[$def2, "col1 =", [False, ['param']]],
			[$def2, "col1 = x", [False, ['param']]],
		];
	}



	/**
	 * @dataProvider dataVariants
	 * /
	function _____testVariants($def, $src, $expected)
	{
		$this->assertEquals($expected, (new Parser($def))->parseRaw($src));
	}



	function dataVariants()
	{
		$white = Parser::pattern(Null, '\s+', False);
		//*
		$not = Parser::pattern('not', 'NOT');
		$boolOperator = Parser::oneOf('bool', [
			Parser::pattern(Null, 'AND'),
			Parser::pattern(Null, 'OR'),
		]);
		$expr2 = Parser::sequence('expr', [ // col1 != :par1
			Parser::pattern('column', '[a-z0-9]+'),
			$white,
			Parser::oneOf('op', [
				Parser::pattern(Null, 'NOT LIKE'),
				Parser::pattern(Null, 'NOT IN'),
				Parser::pattern(Null, 'LIKE'),
				Parser::pattern(Null, 'IN'),
				Parser::pattern(Null, '[=><!]{1,2}'),
			]),
			$white,
			Parser::pattern('param', '\:[a-z0-9]+'),
		]);
		//* /
		$def1 = Parser::variants(Null, [
			Parser::pattern('column', '[a-z0-9]+'),
			Parser::pattern('param', '\:[a-z0-9]+'),
		]);
		$def2 = Parser::variants(Null, [
			Parser::pattern('column', '[a-z0-9]+'),
			$white,
			Parser::pattern('param', '\:[a-z0-9]+'),
		]);
		$def3 = Parser::variants(Null, [
			Parser::pattern('column', '[a-z0-9]+'),
			Parser::pattern('param', '\:[a-z0-9]+'),
			$white,
		]);
		$expr = Parser::sequence('expr', [
			Parser::pattern('column', '[a-z0-9]+'),
			$white,
			Parser::pattern('param', '\:[a-z0-9]+'),
		]);
		$def4 = Parser::variants(Null, [$expr, $white]);
		$andornot = Parser::variants('root', [
			$expr2,
			Parser::oneOf(Null, [
				Parser::sequence('and/or not', [
					$white, $boolOperator, $white, $not, $white
				]),
				Parser::sequence('and/or', [
					$white, $boolOperator, $white
				]),
			]),
		]);
		$notexpr = Parser::variants(Null, [
			Parser::oneOf(Null, [
				Parser::sequence('not expr', [
					$not, $white, $expr2
				]),
				$expr2,
			]),
			Parser::sequence('and/or', [
				$white, $boolOperator, $white
			]),
		]);

		return [
			[$def1, "a:b", [self::makeToken(Null, [
				self::makeToken('column', "a", 0, 1),
				self::makeToken('param', ":b", 1, 3),
				], 0, 3), ['column']]],
			[$def1, "aa:b", [self::makeToken(Null, [
				self::makeToken('column', "aa", 0, 2),
				self::makeToken('param', ":b", 2, 4),
				], 0, 4), ['column']]],
			[$def1, "abc", [self::makeToken(Null, [
				self::makeToken('column', "abc", 0, 3),
				], 0, 3), ['param']]],
			[$def1, "abc?def", [self::makeToken(Null, [
				self::makeToken('column', "abc", 0, 3),
				], 0, 3), ['param']]],
			[$def1, "@aa:b", [False, ['column', 'param']]],
			[$def1, "abc:def:efg:hijkl", [self::makeToken(Null, [
				self::makeToken('column', "abc", 0, 3),
				self::makeToken('param', ":def", 3, 7),
				], 0, 7), ['column']]],
			[$def2, 'a:b :cdd', [self::makeToken(Null, [
				self::makeToken('column', 'a', 0, 1),
				self::makeToken('param', ':b', 1, 3),
				self::makeToken('param', ':cdd', 4, 8),
				], 0, 8), ['column']]],
			[$def3, 'a:b :cdd', [self::makeToken(Null, [
				self::makeToken('column', 'a', 0, 1),
				self::makeToken('param', ':b', 1, 3),
				self::makeToken('param', ':cdd', 4, 8),
				], 0, 8), ['column']]],
			[$def4, 'a :b x :cdd', [self::makeToken(Null, [
				self::makeToken('expr', [
					self::makeToken('column', 'a', 0, 1),
					self::makeToken('param', ':b', 2, 4),
				], 0, 4),
				self::makeToken('expr', [
					self::makeToken('column', 'x', 5, 6),
					self::makeToken('param', ':cdd', 7, 11),
				], 5, 11),
				], 0, 11), []]],
			[$def4, "a :b x :cdd\nJakSeDa :blblbl ole", [self::makeToken(Null, [
					self::makeToken('expr', [
						self::makeToken('column', 'a', 0, 1),
						self::makeToken('param', ':b', 2, 4),
					], 0, 4),
					self::makeToken('expr', [
						self::makeToken('column', 'x', 5, 6),
						self::makeToken('param', ':cdd', 7, 11),
					], 5, 11),
					self::makeToken('expr', [
						self::makeToken('column', 'JakSeDa', 12, 19),
						self::makeToken('param', ':blblbl', 20, 27),
					], 12, 27),
				], 0, 28), ['expr']]], // 28, protože to matchne ještě separator whitechar
			[$andornot, "a != :b AND c = :c", [self::makeToken('root', [
					self::makeToken('expr', [
						self::makeToken('column', 'a', 0, 1),
						self::makeToken('op', '!=', 2, 4),
						self::makeToken('param', ':b', 5, 7),
					], 0, 7),
					self::makeToken('and/or', [
						self::makeToken('bool', 'AND', 8, 11),
					], 7, 12),
					self::makeToken('expr', [
						self::makeToken('column', 'c', 12, 13),
						self::makeToken('op', '=', 14, 15),
						self::makeToken('param', ':c', 16, 18),
					], 12, 18),
				], 0, 18), []]],
			[$andornot, "a != :b AND NOT c = :c", [self::makeToken('root', [
					self::makeToken('expr', [
						self::makeToken('column', 'a', 0, 1),
						self::makeToken('op', '!=', 2, 4),
						self::makeToken('param', ':b', 5, 7),
					], 0, 7),
					self::makeToken('and/or not', [
						self::makeToken('bool', 'AND', 8, 11),
						self::makeToken('not', 'NOT', 12, 15),
					], 7, 16),
					self::makeToken('expr', [
						self::makeToken('column', 'c', 16, 17),
						self::makeToken('op', '=', 18, 19),
						self::makeToken('param', ':c', 20, 22),
					], 16, 22),
				], 0, 22), []]],
			[$notexpr, "a != :b AND c = :c", [self::makeToken(Null, [
					self::makeToken('expr', [
						self::makeToken('column', 'a', 0, 1),
						self::makeToken('op', '!=', 2, 4),
						self::makeToken('param', ':b', 5, 7),
					], 0, 7),
					self::makeToken('and/or', [
						self::makeToken('bool', 'AND', 8, 11),
					], 7, 12),
					self::makeToken('expr', [
						self::makeToken('column', 'c', 12, 13),
						self::makeToken('op', '=', 14, 15),
						self::makeToken('param', ':c', 16, 18),
					], 12, 18),
				], 0, 18), ['and/or']]], /// @FIXME Proč je tam ten and/or, vždyt je to v cajku?!
			[$notexpr, "a != :b AND NOT c = :c", [self::makeToken(Null, [
					self::makeToken('expr', [
						self::makeToken('column', 'a', 0, 1),
						self::makeToken('op', '!=', 2, 4),
						self::makeToken('param', ':b', 5, 7),
					], 0, 7),
					self::makeToken('and/or', [
						self::makeToken('bool', 'AND', 8, 11),
					], 7, 12),
					self::makeToken('not expr', [
						self::makeToken('not', 'NOT', 12, 15),
						self::makeToken('expr', [
							self::makeToken('column', 'c', 16, 17),
							self::makeToken('op', '=', 18, 19),
							self::makeToken('param', ':c', 20, 22),
						], 16, 22),
					], 12, 22),
				], 0, 22), ['and/or']]],/// @FIXME Proč je tam ten and/or, vždyt je to v cajku?!
		];
	}



	function _____testSampleX()
	{
		$def = Parser::pattern('not', 'NOT');
		$parser = new Parser($def);
		//~ dump($parser->parseRaw("a :b x :cdd\nJakSeDa :blblbl ole"));
		//~ dump($parser->parse("not"));
	}
	//*/


	private static function makeToken($type, $content, $start, $end)
	{
		return (object) [
			'name' => $type,
			'content' => $content,
			'start' => $start,
			'end' => $end,
		];
	}

}
