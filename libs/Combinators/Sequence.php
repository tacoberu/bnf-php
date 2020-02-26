<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Ref;
use Taco\BNF\Utils;
use Taco\BNF\BaseCombinator;
use Taco\BNF\Combinator;


/**
 * Prvky musí jít jeden za druhým.
 */
class Sequence implements Combinator
{

	use BaseCombinator;

	private $items;


	function __construct($name, array $options, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
		$this->items = $options;
	}



	function getExpectedNames()
	{
		if ($this->name) {
			return [$this->name];
		}
		$res = [];
		foreach ($this->items as $node) {
			$res = array_merge($res, $node->getExpectedNames());
		}
		return $res;
	}



	/**
	 * Zjistí, zda jde matchnout číselnou hodnotu pro aktuální offset.
	 * - žádné matchnutí = [false, [$name]]
	 * - matchnutí části = [False, [$name té části]]
	 * - úspěšné matchnutí všeho, konec nás nezajímá = [Token, []]
	 * @return Fail|Token
	 */
	function scan($src, $offset, array $bank)
	{
		$bank = Utils::addToBank($bank, $this);
		$res = [];
		foreach ($this->items as $i => $node) {
			if ($node instanceof Ref) {
				$node = $bank[$node->name];
			}
			else {
				$bank = Utils::addToBank($bank, $node);
			}
			list($token, $expected) = $node->scan($src, $offset, $bank);
			if (empty($token)) {
				//~ dump(['@' . __method__ . ':' . __line__, $expected, debug_backtrace(0, 3)]);
				if ($node->isOptional()) {
					continue;
				}
				if (count($expected)) {
					return [False, $expected];
				}
				return [False, self::buildExpected($this->name, $this->items, $i, $offset)];
			}
			$res[] = $token;
			$offset = $token->end;
		}

		$first = reset($res);
		$last = end($res);
		return [new Token($this, Utils::filterCapture($res), $first->start, $last->end), []];
	}



	private static function buildExpected($default, $options, $index, $offset)
	{
		$names = $options[$index]->getExpectedNames();
		if (count($names)) {
			$ret = [];
			foreach ($names as $x) {
				$ret[$x] = $offset;
			}
			return $ret;
		}
		if ($default) {
			return [$default => $offset];
		}
		return [];
	}

}
