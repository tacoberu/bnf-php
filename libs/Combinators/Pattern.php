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


class Pattern implements Combinator
{

	use BaseCombinator;

	/**
	 * @var array<string>
	 */
	private $patterns;


	/**
	 * @param ?string $name
	 * @param array<string> $patterns
	 * @param bool $capture
	 */
	function __construct($name, array $patterns, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
		$this->patterns = $patterns;
	}



	/**
	 * @return array<string>
	 */
	function getExpectedNames()
	{
		if (empty($this->name)) {
			return [];
		}
		return [$this->name];
	}



	/**
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank
	 * @return array{0: false|Token, 1: array<string, int>}
	 */
	function scan($src, $offset, array $bank)
	{
		if ($ret = Utils::scanPattern($this, $this->patterns, $src, $offset)) {
			return [$ret, []];
		}
		return [False, Utils::buildExpected([$this], $offset)];
	}

}
