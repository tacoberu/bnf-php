<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Utils;
use Taco\BNF\BaseCombinator;
use Taco\BNF\Combinator;


class Pattern implements Combinator
{

	use BaseCombinator;

	private $patterns;


	function __construct($name, array $patterns, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
		$this->patterns = $patterns;
	}



	function getExpectedNames()
	{
		if (empty($this->name)) {
			return [];
		}
		return [$this->name];
	}



	/**
	 * Zjistí, zda jde matchnout číselnou hodnotu pro aktuální offset.
	 * @return [False|Token, $expected: array]
	 */
	function scan($src, $offset, array $bank)
	{
		if ($ret = Utils::scanPattern($this, $this->patterns, $src, $offset)) {
			return [$ret, []];
		}
		return [False, Utils::buildExpected([$this], $offset)];
	}

}
