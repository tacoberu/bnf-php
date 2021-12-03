<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Utils;
use Taco\BNF\Ref;
use Taco\BNF\BaseCombinator;
use Taco\BNF\Combinator;


/**
 * Returns all content up to the pattern. Stops before the pattern.
 */
class Until implements Combinator
{

	use BaseCombinator;


	/**
	 * @var array<string>
	 */
	private $options;


	/**
	 * @param ?string $name
	 * @param array<string> $options
	 * @param bool $capture
	 */
	function __construct($name, array $options, $capture = True)
	{
		$this->name = $name;
		$this->options = $options;
		$this->capture = $capture;
	}



	/**
	 * Returns all content up to the pattern. Stops before the pattern.
	 *
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank
	 * @return array{0: false|Token, 1: array<string, int>}
	 */
	function scan($src, $offset, array $bank)
	{
		if ($offset > strlen($src)) {
			return [False, []];
		}
		$bank = Utils::addToBank($bank, $this);
		if ($token = Utils::scanPattern($this, $this->options, $src, $offset, False)) {
			return [new Token($this,
					substr($src, $offset, $token->start - $offset),
					$offset,
					$token->start
					), []];
		}
		return [new Token($this,
				substr($src, $offset),
				$offset,
				strlen($src)
				), []];
	}



	/**
	 * @return array<string>
	 */
	function getExpectedNames()
	{
		if ($this->name) {
			return [$this->name];
		}
		$res = [];
		foreach ($this->options as $node) {
			//~ $res = array_merge($res, $node->getExpectedNames());
		}
		return $res;
	}

}
