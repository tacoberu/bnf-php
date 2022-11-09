<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use PHPUnit\Framework\TestCase;
use LogicException;


class ManyTest extends TestCase
{


	function testCorrect()
	{
		$sep = new Whitechars(Null, False);
		$pair = new Sequence('pair', [
			new Pattern('name', ['~[a-z][a-zA-Z0-9]+~']),
			new Pattern(Null, ['~\s*\:\s*~']),
			new Pattern('value', ['~[A-Z][a-zA-Z0-9]+~']),
			$sep->setOptional(),
		]);
		$parser = new Many(Null, $pair);

		$src = 'col : Param';
		list($token,) = $parser->scan($src, 0, []);
		$this->assertSame('col : Param', (string) $token);
		$this->assertCount(1, $token->content);
		$this->assertCount(3, $token->content[0]->content);
		$this->assertSame(0, $token->start);
		$this->assertSame(11, $token->end);

		$src = 'col : Param col2: Value';
		list($token,) = $parser->scan($src, 0, []);
		$this->assertSame('col : Paramcol2: Value', (string) $token);
	}


}
