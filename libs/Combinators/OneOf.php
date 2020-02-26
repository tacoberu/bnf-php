<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Ref;
use Taco\BNF\BaseCombinator;
use Taco\BNF\Combinator;
use Taco\BNF\Utils;


/**
 * Máme několik možností, ale vybere se pouze jedna.
 */
class OneOf implements Combinator
{

	use BaseCombinator;

	private $options;


	function __construct($name, array $options, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
		$this->options = $options;
	}



	function getExpectedNames()
	{
		if ($this->name) {
			return [$this->name];
		}
		$res = [];
		foreach ($this->options as $node) {
			$res = array_merge($res, $node->getExpectedNames());
		}
		return $res;
	}



	/**
	 * Zjistí, zda jde matchnout číselnou hodnotu pro aktuální offset.
	 * @return Fail|Token
	 */
	function scan($src, $offset, array $bank)
	{
		$bank = Utils::addToBank($bank, $this);
		foreach ($this->options as $node) {
			if ($node instanceof Ref) {
				$node = $bank[$node->name];
			}
			list($token,) = $node->scan($src, $offset, $bank);
			if ($token) {
				return [Utils::flatting([$token])[0], []];
			}
		}

		return [False, self::buildExpected($this->options, $offset)];
	}



	private static function buildExpected(array $xs, $offset)
	{
		$ret = [];
		foreach ($xs as $x) {
			if ($x->getName()) {
				$ret[$x->getName()] = $offset;
			}
		}
		return $ret;
	}
}
