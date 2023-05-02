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
use LogicException;


/**
 * The elements must go one after the other.
 */
class Sequence implements Combinator
{

	use BaseCombinator;

	/**
	 * @var array<Combinator>
	 */
	private $items;


	/**
	 * @param string $name
	 * @param array<Combinator> $options
	 * @param bool $capture
	 */
	function __construct($name, array $options, $capture = True)
	{
		self::assertOptionsCount($options);
		$this->name = $name;
		$this->capture = $capture;
		$this->items = $options;
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
		foreach ($this->items as $node) {
			$res = array_merge($res, $node->getExpectedNames());
		}
		return $res;
	}



	/**
	 * Checks if the numeric value for the current offset can be matched.
	 * - no match = [false, [$name]]
	 * - match part = [False, [$name of that part]]
	 * - successful matching of everything, we don't care about the end = [Token, []]
	 *
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank
	 * @return array{0: false|Token, 1: array<string, int>}
	 */
	function scan($src, $offset, array $bank)
	{
		$bank = Utils::addToBank($bank, $this);
		$res = [];
		$prevExpected = [];
		foreach ($this->items as $i => $node) {
			if ($node instanceof Ref) {
				$node = $bank[$node->name];
			}
			else {
				$bank = Utils::addToBank($bank, $node);
			}
			list($token, $expected) = $node->scan($src, $offset, $bank);
			if (empty($token)) {
				if ($node->isOptional()) {
					continue;
				}
				if (count($expected)) {
					return [False, array_merge($prevExpected, $expected)];
				}
				if (count($prevExpected)) {
					// @TODO without test
					return [False, $prevExpected];
				}
				return [False, self::buildExpected($this->name, $this->items, $i, $offset)];
			}
			$prevExpected = $expected;
			$res[] = $token;
			$offset = $token->end;
		}

		$first = reset($res);
		$last = end($res);
		if (is_bool($first)) {
			// @TODO without test
			throw new LogicException("Sequence combinator must containt minimal two items. (first)");
		}
		if (is_bool($last)) {
			// @TODO without test
			throw new LogicException("Sequence combinator must containt minimal two items. (last)");
		}
		$res = Utils::filterCapture($res);
		$res = Utils::flatting($res);
		if (empty($this->getName()) && count($res) == 1) {
			return [reset($res), []];
		}

		// The returned token has the scope of the $src part it actually processed. Including unaccounted tokens,
		// which are not in the result. So a token can have a start:stop of 5:10, and own a token that will be 6:9.
		return [new Token($this, $res, $first->start, $last->end), []];
	}



	/**
	 * @param string $default
	 * @param array<Combinator> $options
	 * @param int $index
	 * @param int $offset
	 * @return array<string, int>
	 */
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



	/**
	 * @param array<Combinator> $xs
	 * @return void
	 */
	private static function assertOptionsCount(array $xs)
	{
		if (count($xs) < 2) {
			throw new LogicException("Sequence combinator must containt minimal two items.");
		}
	}
}
