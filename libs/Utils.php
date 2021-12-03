<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use Taco\BNF\Combinators\Sequence;
use LogicException;


class Utils
{

	/**
	 * Na $src se zpracují regulární výrazi a v při prvním úspěchu to vrátí vytvořený
	 * token $typu. Defaultně přistupuje ke všem patternům jako kdyby byly ukotveny k začátku. Viz
	 * přepínač $checkStart.
	 *
	 * @param array<string> $patterns Kolekce regulárních výrazů.
	 * @param string $src
	 * @param int $offset
	 * @param bool $checkStart Umožní samoposun. Například číslo pro $src: "abcds123" a $offset: 3 to najde shodu. Ale až od pozice 5.
	 * @return Token|False
	 */
	static function scanPattern(Combinator $type, array $patterns, $src, $offset, $checkStart = True)
	{
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $src, $out, PREG_OFFSET_CAPTURE, $offset) && self::checkStartOffset($out[0][1], $offset, $checkStart)) {
				if (count($out) == 1) {
					self::assertEmptyString($out[0][0], $pattern);
					return new Token($type, $out[0][0], $out[0][1], $out[0][1] + strlen($out[0][0]));
				}
				elseif (count($out) == 2) {
					self::assertEmptyString($out[1][0], $pattern);
					return new Token($type, $out[1][0], $out[1][1], $out[1][1] + strlen($out[1][0]));
				}
				else {
					throw new LogicException("A pattern with multiple parentheses is not supported: '$pattern'.");
				}
			}
		}
		return False;
	}



	/**
	 * @param array<string, Combinator> $bank
	 * @return array<string, Combinator>
	 */
	static function addToBank(array $bank, Combinator $node)
	{
		if ($node->getName()) {
			$bank[$node->getName()] = $node;
		}
		return $bank;
	}



	/**
	 * @param array<Combinator> $xs
	 * @param int $offset
	 * @return array<string, int>
	 */
	static function buildExpected(array $xs, $offset)
	{
		$ret = [];
		foreach ($xs as $x) {
			if ($x->getName()) {
				$ret[$x->getName()] = $offset;
			}
		}
		return $ret;
	}



	/**
	 * Odstraní ty, které se nemamí pamatovat.
	 * @param array<Token> $res
	 * @return array<Token>
	 */
	static function filterCapture(array $res)
	{
		$ret = [];
		foreach ($res as $x) {
			if ($x->isCapture()) {
				$ret[] = $x;
			}
		}
		return $ret;
	}



	/**
	 * @param array<Token> $src
	 * @return array<Token>
	 */
	static function flatting(array $src)
	{
		$ret = [];
		foreach ($src as $x) {
			switch (True) {
				case $x->type instanceof Sequence && is_array($x->content) && count($x->content) === 1 && empty($x->getName()) && reset($x->content) instanceof Token:
					$item = reset($x->content);
					$item->start = $x->start;
					$item->end = $x->end;
					$ret[] = $item;
					break;
				/* To zdrcávání není úplně domyšlené...
				case $x->type instanceof Sequence && count($x->content) === 1:
					$item = reset($x->content);
					if (empty($item->getName())) {
						$item->type = $item->type->setName($x->getName());
						$item->start = $x->start;
						$item->end = $x->end;
					}
					$ret[] = $item;
					break;//*/
				default:
					$ret[] = $x;
			}
		}
		return $ret;
	}



	/**
	 * Mějme definovaný blok, kde začátek je definovaný pomocí $start a konec
	 * pomocí $end. Vrátí nám indexy startu a konce. Přičemž zohledňuje zanoření.
	 * @TODO Neumí escapovat.
	 *
	 * @param string $startmarker
	 * @param string $endmarker
	 * @param string $src
	 * @param int $offset
	 *
	 * @return array<int|false> [index-start, index-end]
	 */
	static function lookupBlock($startmarker, $endmarker, $src, $offset)
	{
		if ($offset >= strlen($src)) {
			return [False, False];
		}
		$startIndex = strpos($src, $startmarker, $offset);
		$endIndex = strpos($src, $endmarker, $offset);

		if ($startIndex !== False) {
			$endIndex = self::lookupEndIndex($startmarker, $endmarker, $src, $startIndex + 1);
			return [$startIndex, $endIndex];
		}
		return [False, False];
	}



	/**
	 * @param string $start
	 * @param string $end
	 * @param string $src
	 * @param int $offset
	 * @return int<0, max>|false
	 */
	private static function lookupEndIndex($start, $end, $src, $offset)
	{
		$startIndex = strpos($src, $start, $offset);
		$endIndex = strpos($src, $end, $offset);
		if ($startIndex !== False && $startIndex < $endIndex) {
			if ($endIndex = self::lookupEndIndex($start, $end, $src, $startIndex + 1)) {
				$endIndex = self::lookupEndIndex($start, $end, $src, $endIndex + 1);
			}
			return $endIndex;
		}
		return $endIndex;
	}



	/**
	 * @param mixed $val
	 * @param mixed $expected
	 * @param bool $checkStart
	 * @return bool
	 */
	private static function checkStartOffset($val, $expected, $checkStart)
	{
		if ( ! $checkStart) {
			return True;
		}
		return $val === $expected;
	}



	/**
	 * @param string $src
	 * @param string $pattern For using in a exception message.
	 * @return void
	 */
	private static function assertEmptyString($src, $pattern)
	{
		if (strlen($src) == 0) {
			throw new LogicException("The pattern '$pattern' corresponds to an empty string.");
		}
	}

}
