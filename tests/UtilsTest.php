<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use PHPUnit_Framework_TestCase;
use LogicException;
use Taco\BNF\Combinators\Sequence;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Variants;


class UtilsTest extends PHPUnit_Framework_TestCase
{

	function _testDev()
	{
		$src = '{abc{def}efg{{xyz}hch}} zdf';
		$res = Utils::lookupBlock('{', '}', $src, 0);
		dump($res);
		dump(substr($src, $res[0], ($res[1] - $res[0]) + 1));
	}



	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($src, $offset, $expected)
	{
		$this->assertEquals($expected, Utils::lookupBlock('{', '}', $src, $offset));
	}



	function dataCorrect()
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
