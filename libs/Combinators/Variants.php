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
 * Máme několik možností, které se navzájem střídají. Můžeme rozlišit, které
 * mají být na začátku, a které na konci. Nemohou být zvoleny dvě stejné za sebou - musí se střídat.
 */
class Variants implements Combinator
{

	use BaseCombinator;

	private $options;
	private $first;
	private $last;


	function __construct($name, array $options, array $first = Null, array $last = Null, $capture = True)
	{
		self::assertOptionsCount($options);
		$this->name = $name;
		$this->capture = $capture;
		$this->options = $options;
		$this->first = $first ?: $options;
		$this->last = $last ?: $options;
	}



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
	 * Zjistí, zda jde matchnout číselnou hodnotu pro aktuální offset.
	 * - žádné matchnutí = [false, [$name]]
	 * - úspěšné matchnutí, ale ještě nejsme na konci = [Token, [$name]]
	 * - úspěšné matchnutí, a jsme na konci = [Token, []]
	 *
	 * @return False|Token
	 */
	function scan($src, $offset, array $bank)
	{
		$bank = Utils::addToBank($bank, $this);
		$res = [];
		list($token, $expected, $sel) = self::parseFrom($this->first, null, $src, $offset, $bank);
		// Zádné matchnutí
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

		// Poslední odebereme a nahradíme podle vzoru $last. Odebrat můžem i více, protože více jich může neodpovídat $last.
		while (count($res)) {
			// Odstranit poslední.
			$last = array_pop($res);

			// A přidat znova
			list($token, ) = self::parseFrom($this->last, null, $src, $last->start, $bank);
			if ($token) {
				$res[] = $token;
				break;
			}
		}

		if (empty($res)) {
			// Získali jsme jen jeden záznam. Ten odpovídá množině prvních, ale neodpovídá množině poslední.
			return [False, count($expected) ? $expected : self::buildExpected($this->last, $offset)];
		}

		$last = end($res);

		// Úspěšné matchnutí, a jsme na konci = [Token, []]
		//~ $expected = [];

		// Úspěšné matchnutí, ale ještě nejsme na konci = [Token, [$name]]
		if (empty($expected) && strlen($src) > ($offset + 1)) {
			$expected = self::buildExpected($this->last, $last->end);
		}

		$res = Utils::filterCapture($res);
		$res = Utils::flatting($res);

		return [new Token($this, $res, $start, $last->end), $expected];
	}



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



	private static function assertOptionsCount(array $xs)
	{
		if (count($xs) < 2) {
			throw new LogicException("Variants combinator must containt minimal two variant.");
		}
	}

}
