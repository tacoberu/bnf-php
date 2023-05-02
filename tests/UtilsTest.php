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
use Taco\BNF\Combinators\Match_;


class UtilsTest extends TestCase
{


	/**
	 * @dataProvider dataScanPatternIllegalPattern
	 */
	function testScanPatternIllegalPattern(array $patterns)
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('The pattern \'' . $patterns[0] . '\' corresponds to an empty string.');
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		$comb = $this->createMock(Combinator::class);
		Utils::scanPattern($comb, $patterns, $src, 0);
	}



	function dataScanPatternIllegalPattern()
	{
		return [
			[['~[a-z]*~']],
			[['~([a-z]*)~']],
			[['~([a-z]*)|([0-9]*)~']],
		];
	}



	function testScanPatternIllegalPatternOfMultipleParentals()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('A pattern with multiple parentheses is not supported: \'~(([a-z]+))~\'.');
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		$comb = $this->createMock(Combinator::class);
		Utils::scanPattern($comb, ['~(([a-z]+))~'], $src, 1);
	}



	/**
	 * @dataProvider dataScanPatternSuccess
	 */
	function testScanPatternSuccess(string $src, array $patterns, int $offset, array $expected)
	{
		$comb = $this->createMock(Combinator::class);
		$token = Utils::scanPattern($comb, $patterns, $src, $offset);
		$this->assertEquals(new Token($comb, $expected[0], $expected[1], $expected[2]), $token);
	}



	function dataScanPatternSuccess()
	{
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		$patterns1 = ['~[a-z]+~'];
		$patterns2 = ['~[a-z]+~', '~{~'];
		$patterns3 = ['~([a-z]+)~'];
		return [
			[$src, $patterns1, 1, ["abc", 1, 4]],
			[$src, $patterns1, 2, ["bc", 2, 4]],
			[$src, $patterns1, 3, ["c", 3, 4]],
			[$src, $patterns1, 5, ["def", 5, 8]],
			[$src, $patterns2, 0, ["{", 0, 1]],
			[$src, $patterns3, 1, ["abc", 1, 4]],
			['    ', ['~[\s]+~'], 1, ["   ", 1, 4]],
		];
	}



	/**
	 * @dataProvider dataScanPatternNotMatch
	 */
	function testScanPatternNotMatch($src, $offset, $expected)
	{
		$comb = $this->createMock(Combinator::class);
		$this->assertFalse($expected, Utils::scanPattern($comb, ['~[a-z]+~'], $src, $offset));
	}



	function dataScanPatternNotMatch()
	{
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		return [
			[$src, 0, False],
			[$src, 4, False],
			['1234', 0, False],
			['1234', 2, False],
			['1234', 4, False],
			['1234', 5, False],
			['1234', 6, False],
		];
	}



	function testScanPattern_noCheckStart()
	{
		$comb = $this->createMock(Combinator::class);
		$token = Utils::scanPattern($comb, ['~[0-9]+~'], "abcds123", 3, False);
		$this->assertEquals(new Token($comb, '123', 5, 8), $token);
	}



	/**
	 * @dataProvider dataLookupBlock
	 */
	function testLookupBlock(string $startmarker, string $endmarker, string $src, $offset, $expected)
	{
		$this->assertEquals($expected, Utils::lookupBlock($startmarker, $endmarker, $src, $offset));
	}



	function dataLookupBlock()
	{
		return [
			['{', '}', '', 0, [False, False]],
			['{', '}', 'abc', 0, [False, False]],
			['{', '}', 'abc}', 0, [False, False]],
			['{', '}', '{abc}', 0, [0, 4]],
			['{', '}', '{abc', 0, [0, False]],
			['{', '}', '{abc{def}}', 0, [0, 9]],
			['{', '}', '{abc{def}}', 1, [4, 8]],
			['{', '}', '{abc{def}}', 2, [4, 8]],
			['{', '}', '{abc{def}}', 3, [4, 8]],
			['{', '}', '{abc{def}}', 4, [4, 8]],
			['{', '}', '{abc{def}}', 5, [False, False]],
			['{', '}', '{abc{def}}', 500, [False, False]],
			['{', '}', '{abc{def}efg}', 0, [0, 12]],
			['{', '}', '{abc{def}efg{hch}}', 0, [0, 17]],
			['{', '}', '{abc{def}efg{{xyz}hch}} zdf', 0, [0, 22]],

			['<<', '>>', '', 0, [False, False]],
			['<<', '>>', 'abc', 0, [False, False]],
			['<<', '>>', 'abc>>', 0, [False, False]],
			['<<', '>>', '<<abc>>', 0, [0, 5]],
			['<<', '>>', '<<abc', 0, [0, False]],
			['<<', '>>', '<<abc<<def>>>>', 0, [0, 11]],
			['<<', '>>', '<<abc<<def>>>>', 1, [5, 10]],
			['<<', '>>', '<<abc<<def>>>>', 2, [5, 10]],
			['<<', '>>', '<<abc<<def>>>>', 3, [5, 10]],
			['<<', '>>', '<<abc<<def>>>>', 4, [5, 10]],
			['<<', '>>', '<<abc<<def>>>>', 6, [False, False]],
			['<<', '>>', '<<abc<<def>>>>', 500, [False, False]],
			['<<', '>>', '<<abc<<def>>efg>>', 0, [0, 15]],
			['<<', '>>', '<<abc<<def>>efg<<hch>>>>', 0, [0, 21]],
			['<<', '>>', '<<abc<<def>>efg<<<<xyz>>hch>>>> zdf', 0, [0, 29]],

			['"', '"', '', 0, [False, False]],
			['"', '"', 'abc', 0, [False, False]],
			['"', '"', 'abc"', 0, [3, False]],
			['"', '"', '"abc"', 0, [0, 4]],
			['"', '"', '"abc', 0, [0, False]],
			['"', '"', '"abc"def""', 0, [0, 4]],
			['"', '"', '"abc"def""', 1, [4, 8]],
			['"', '"', '"abc"def""', 2, [4, 8]],
			['"', '"', '"abc"def""', 3, [4, 8]],
			['"', '"', '"abc"def""', 4, [4, 8]],
			['"', '"', '"abc"def""', 5, [8, 9]],
			['"', '"', '"abc"def""', 500, [False, False]],
			//~ ['"', '"', '"abc\"def"efg""xyz"hch"" zdf', 0, [0, 22]],
		];
	}



	function testFilterCapture_Empty()
	{
		$src = [];
		$this->assertSame([], Utils::filterCapture($src));
	}



	function testFilterCapture_Noop()
	{
		$src = [new Token(new Match_(Null, ['A']), 'A', 0, 1)];
		$this->assertSame($src, Utils::filterCapture($src));
	}



	function testFilterCapture()
	{
		$src = [
			new Token(new Sequence(Null, [
				new Match_(Null, ['<'], False),
				new Match_(Null, ['A']),
				new Match_(Null, ['>'], False),
				], False), 'A', 2, 3),
			new Token(new Match_(Null, ['A']), 'A', 1, 3),
		];

		$this->assertEquals([
			new Token(new Match_(Null, ['A']), 'A', 1, 3)
			]
			, Utils::filterCapture($src));
	}



	function testFlatting_Empty()
	{
		$src = [];
		$this->assertSame([], Utils::flatting($src));
	}



	function testFlatting_Noop()
	{
		$src = [new Token(new Match_(Null, ['A']), 'A', 0, 1)];
		$this->assertSame($src, Utils::flatting($src));
	}



	function testFlatting()
	{
		$src = [new Token(new Sequence(Null, [
			new Match_(Null, ['<'], False),
			new Match_(Null, ['A']),
			new Match_(Null, ['>'], False),
			]), [new Token(new Match_(Null, ['A']), 'A', 1, 2)], 1, 3)];
		$this->assertEquals([
			new Token(new Match_(Null, ['A']), 'A', 1, 3)
			]
			, Utils::flatting($src));
	}

}
