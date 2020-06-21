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


class UtilsTest extends TestCase
{


	function testScanPatternIllegalPattern()
	{
		$this->expectException(LogicException::class);
		$this->expectExceptionMessage('The pattern \'~[a-z]*~\' corresponds to an empty string.');
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		$comb = $this->createMock(Combinator::class);
		Utils::scanPattern($comb, ['~[a-z]*~'], $src, 0);
	}



	/**
	 * @dataProvider dataScanPattern
	 */
	function testScanPattern($src, $offset, $expected)
	{
		$comb = $this->createMock(Combinator::class);
		if (is_bool($expected)) {
			$this->assertFalse($expected, Utils::scanPattern($comb, ['~[a-z]+~'], $src, $offset));
		}
		else {
			$token = Utils::scanPattern($comb, ['~[a-z]+~'], $src, $offset);
			$this->assertEquals(new Token($comb, $expected[0], $expected[1], $expected[2]), $token);
		}
	}



	function dataScanPattern()
	{
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		return [
			[$src, 0, False],
			[$src, 1, ["abc", 1, 4]],
		];
	}



	/**
	 * @dataProvider dataLookupBlock
	 */
	function testLookupBlock($src, $offset, $expected)
	{
		$this->assertEquals($expected, Utils::lookupBlock('{', '}', $src, $offset));
	}



	function dataLookupBlock()
	{
		return [
			['', 0, [False, False]],
			['abc', 0, [False, False]],
			['abc}', 0, [False, False]],
			['{abc}', 0, [0, 4]],
			['{abc', 0, [0, False]],
			['{abc{def}}', 0, [0, 9]],
			['{abc{def}}', 1, [4, 8]],
			['{abc{def}}', 2, [4, 8]],
			['{abc{def}}', 3, [4, 8]],
			['{abc{def}}', 4, [4, 8]],
			['{abc{def}}', 5, [False, False]],
			['{abc{def}}', 500, [False, False]],
			['{abc{def}efg}', 0, [0, 12]],
			['{abc{def}efg{hch}}', 0, [0, 17]],
			['{abc{def}efg{{xyz}hch}} zdf', 0, [0, 22]],
		];
	}

}
