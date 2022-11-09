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
 * Opakující se vzor.
 */
class Many implements Combinator
{

	use BaseCombinator;

	/**
	 * @var Combinator
	 */
	private $pattern;


	/**
	 * @param ?string $name
	 * @param Combinator $pattern
	 * @param bool $capture
	 */
	function __construct($name, Combinator $pattern, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
		$this->pattern = $pattern;
	}



	/**
	 * @return array<string>
	 */
	function getExpectedNames()
	{
		if ($this->name) {
			return [$this->name];
		}
		return $this->pattern->getExpectedNames();
	}



	/**
	 * Zjistí, zda jde matchnout číselnou hodnotu pro aktuální offset.
	 * - žádné matchnutí = [false, [$name]]
	 * - úspěšné matchnutí, ale ještě nejsme na konci = [Token, [$name]]
	 * - úspěšné matchnutí, a jsme na konci = [Token, []]
	 *
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank
	 * @return array{0: false|Token, 1: array<string, int>}
	 */
	function scan($src, $offset, array $bank)
	{
		$start = $offset;
		$bank = Utils::addToBank($bank, $this);
		$res = [];
		$len = strlen($src);
		$expected = [];
		while ($offset < $len) {
			list($token, $expected) = $this->pattern->scan($src, $offset, $bank);
			if (empty($token)) {
				break;
			}
			$res[] = $token;
			$offset = $token->end;
		}

		if (count($res) == 0) {
			return [False, $expected];
		}
		$start = reset($res)->start;
		$last = end($res)->end;

		// Úspěšné matchnutí, ale ještě nejsme na konci = [Token, [$name]]
		if (empty($expected) && strlen($src) > ($offset + 1)) {
			$expected = [$this->getName() => $last];
		}

		$res = Utils::filterCapture($res);
		$res = Utils::flatting($res);

		//~ return [$res, $expected];
		return [new Token($this, $res, $start, $last), $expected];
	}



	/**
	 * @param array<Combinator|Ref> $xs
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

}
