<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Utils;
use Taco\BNF\BaseCombinator;
use Taco\BNF\Combinator;


class Any implements Combinator
{

	use BaseCombinator;


	function __construct($name, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
	}



	/**
	 * @return False|Token
	 */
	function scan($src, $offset, array $bank)
	{
		if ($ret = Utils::scanPattern($this, ['~.+~s',], $src, $offset)) {
			return [$ret, []];
		}
		return [False, Utils::buildExpected([$this], $offset)];
	}



	function getExpectedNames()
	{
		if (empty($this->name)) {
			return [];
		}
		return [$this->name];
	}

}
