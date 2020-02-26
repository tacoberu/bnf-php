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


class Match implements Combinator
{

	use BaseCombinator;


	private $patterns;


	function __construct($name, array $patterns, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
		$this->patterns = $patterns;
	}



	function scan($src, $offset, array $bank)
	{
		if ($ret = $this->match($src, $offset, $bank)) {
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



	/**
	 * Zjistí, zda jde matchnout číselnou hodnotu pro aktuální offset.
	 * @return False|Token
	 */
	private function match($src, $offset, array $bank)
	{
		foreach ($this->patterns as $pattern) {
			if (0 === strcasecmp($pattern, substr($src, $offset, strlen($pattern)))) {
				return new Token($this,
						substr($src, $offset, strlen($pattern)),
						$offset,
						$offset + strlen($pattern)
						);
			}
		}
		return False;
	}

}
