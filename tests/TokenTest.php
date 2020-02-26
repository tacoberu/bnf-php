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


class TokenTest extends PHPUnit_Framework_TestCase
{

	function dataToString()
	{
		$var = new Variants(Null, []);
		$seq = new Sequence(Null, []);
		$ptn = new Pattern(Null, []);
		return [
			['col  = :prop', new Token($seq, [
					new Token($ptn, 'col', 0, 3),
					new Token($ptn, '=', 5, 6),
					new Token($ptn, ':prop', 7, 10),
				], 0, 5)],
			['col = :prop', new Token($seq, [
					new Token($ptn, 'col', 0, 3),
					new Token($ptn, '=', 4, 5),
					new Token($ptn, ':prop', 6, 10),
				], 0, 5)],
			/*['col = :prop', new Token($seq, [
					new Token($ptn, 'col', 0, 3),
					new Token($ptn, '=', 4, 7),
					new Token($ptn, ':prop', 8, 10),
				], 0, 5)],*/
			['aaa bbb ccc', new Token($var, [
					new Token($ptn, 'aaa', 0, 3),
					new Token($ptn, 'bbb', 4, 10),
					new Token($ptn, 'ccc', 11, 15),
				], 0, 5)],
		];
	}



	/**
	 * @dataProvider dataToString
	 */
	function testToString($expected, $ast)
	{
		$this->assertEquals($expected, (string) $ast);
	}

}
