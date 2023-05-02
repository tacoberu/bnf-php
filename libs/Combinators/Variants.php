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
 * We have several options that alternate with each other. We can distinguish which
 * should be at the beginning, and which should be at the end. Two identical ones cannot be selected in a row - they must alternate.
 * We can specify a minimum and maximum number for this to be valid.
 */
class Variants implements Combinator
{

	use BaseCombinator;

	/**
	 * @var array<Combinator>
	 */
	private $options;

	/**
	 * @var array<Combinator>
	 */
	private $first;

	/**
	 * @var array<Combinator>
	 */
	private $last;

	/**
	 * @var ?int If specified, a minimum of $min elements is expected.
	 */
	private $min;

	/**
	 * @var ?int If specified, a maximum of $max elements is expected.
	 */
	private $max;


	/**
	 * @param ?string $name
	 * @param array<Combinator> $options
	 * @param array<Combinator> $first
	 * @param array<Combinator> $last
	 * @param bool $capture
	 */
	function __construct($name, array $options, array $first = Null, array $last = Null, $capture = True)
	{
		self::assertOptionsCount($options);
		$this->name = $name;
		$this->capture = $capture;
		$this->options = $options;
		$this->first = $first ?: $options;
		$this->last = $last ?: $options;
	}



	/**
	 * @param ?int $min If specified, a maximum of $min elements is expected.
	 * @param ?int $max If specified, a maximum of $max elements is expected.
	 * @return self
	 */
	function setBoundary($min, $max)
	{
		$this->min = $min;
		$this->max = $max;
		return $this;
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
		foreach ($this->first as $node) {
			$res = array_merge($res, $node->getExpectedNames());
		}
		return $res;
	}



	/**
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank
	 * @return array{0: false|Token, 1: array<string, int>}
	 */
	function scan($src, $offset, array $bank)
	{
		$bank = Utils::addToBank($bank, $this);
		$res = [];
		list($token, $expected, $sel) = self::parseFrom($this->first, null, $src, $offset, $bank);

		// No match
		if ( ! $token) {
			return [False, count($expected) ? $expected : self::buildExpected($this->first, $offset)];
		}
		$res[] = $token;

		$start = $token->start;
		$offset = $token->end;
		$selected = $sel;
		$len = strlen($src);
		while ($offset < $len) {
			list($token, $expected, $sel) = self::parseFrom($this->options, $selected, $src, $offset, $bank);
			if (empty($token)) {
				break;
			}
			$res[] = $token;
			$selected = $sel;
			$offset = $token->end;
		}

		// We will remove the last one and replace it according to the $last pattern. I can remove more as more may not match $last.
		while (count($res)) {
			// Delete the last one.
			$last = array_pop($res);

			// And add again
			list($token, ) = self::parseFrom($this->last, null, $src, $last->start, $bank);
			if ($token) {
				$res[] = $token;
				break;
			}
		}

		if (empty($res)) {
			// We only got one record. The latter corresponds to the set of the former, but does not correspond to the set of the latter.
			return [False, count($expected) ? $expected : self::buildExpected($this->last, $offset)];
		}

		$last = end($res);

		// Successful match, and we're done = [Token, []]
		//~ $expected = [];

		// Successful match, but we're not done yet = [Token, [$name]]
		if (empty($expected) && strlen($src) > ($offset + 1)) {
			$expected = self::buildExpected($this->last, $last->end);
		}

		$res = Utils::filterCapture($res);
		$res = Utils::flatting($res);

		if ($this->min && count($res) < $this->min) {
			// We have received less than the required number of records.
			return [False, self::buildExpected($this->options, $offset)];
		}

		if ($this->max && count($res) > $this->max) {
			// We have received more than the required number of records.
			return [False, self::buildExpected($this->options, $offset)];
		}

		return [new Token($this, $res, $start, $last->end), $expected];
	}



	/**
	 * @param array<Combinator> $options
	 * @param ?Combinator $skip
	 * @param string $src
	 * @param int $offset
	 * @param array<Combinator> $bank
	 * @return array{false|Token, array<string, int>, ?Combinator}
	 */
	private static function parseFrom($options, $skip, $src, $offset, array $bank = [])
	{
		$expected = [];
		foreach ($options as $node) {
			if ($skip == $node) {
				continue;
			}
			if ($node instanceof Ref) {
				$node = $bank[$node->name];
			}
			list($token, $expected2) = $node->scan($src, $offset, $bank);
			if ($token) {
				return [$token, $expected2, $node];
			}
			$expected = array_merge($expected, $expected2);
		}
		return [False, $expected, Null];
	}



	/**
	 * @param array<Combinator> $xs
	 * @param int $offset
	 * @return array<string, int>
	 */
	private static function buildExpected(array $xs, $offset)
	{
		$ret = [];
		foreach ($xs as $x) {
			$ret = array_merge($ret, $x->getExpectedNames());
		}
		$ret2 = [];
		foreach (array_unique($ret) as $x) {
			$ret2[$x] = $offset;
		}
		return $ret2;
	}



	/**
	 * @param array<Combinator> $xs
	 * @return void
	 */
	private static function assertOptionsCount(array $xs)
	{
		if (count($xs) < 2) {
			throw new LogicException("Variants combinator must containt minimal two variant.");
		}
	}

}
