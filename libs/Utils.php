<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use Taco\BNF\Combinators\Sequence;


class Utils
{

	static function scanPattern(Combinator $type, array $patterns, $src, $offset)
	{
		foreach ($patterns as $pattern) {
			if (preg_match($pattern, $src, $out, PREG_OFFSET_CAPTURE, $offset) && $out[0][1] === $offset) {
				return new Token($type, $out[0][0], $out[0][1], $out[0][1] + strlen($out[0][0]));
			}
		}
		return False;
	}



	static function lookupBlock($start, $end, $src, $offset)
	{
		if ($offset >= strlen($src)) {
			return [False, False];
		}
		$startIndex = strpos($src, $start, $offset);
		$endIndex = strpos($src, $end, $offset);

		//~ dump(['@' . __method__ . ':' . __line__, $startIndex, $endIndex]);
		if ($startIndex !== False) {
			$endIndex = self::lookupEndIndex($start, $end, $src, $startIndex + 1);
			//~ dump(['@' . __method__ . ':' . __line__, $startIndex, $endIndex, substr($src, $startIndex, ($endIndex - $startIndex) + 1)]);
			return [$startIndex, $endIndex];
		}
		return [False, False];
	}



	private static function lookupEndIndex($start, $end, $src, $offset)
	{
		$startIndex = strpos($src, $start, $offset);
		$endIndex = strpos($src, $end, $offset);
		//~ dump(['@' . __method__ . ':' . __line__, $startIndex, $endIndex]);
		if ($startIndex !== False && $startIndex < $endIndex) {
			// end-index hledáme dvakrát, protože jsem u druhé závorky. Další úroven zanoření si řeší rekurze.
			if ($endIndex = self::lookupEndIndex($start, $end, $src, $startIndex + 1)) {
				$endIndex = self::lookupEndIndex($start, $end, $src, $endIndex + 1);
			}
			//~ dump(['@' . __method__ . ':' . __line__, $startIndex, $endIndex, substr($src, $startIndex, ($endIndex - $startIndex) + 1)]);
			return $endIndex;
		}
		return $endIndex;
	}




	static function addToBank(array $bank, Combinator $node)
	{
		if ($node->getName()) {
			$bank[$node->getName()] = $node;
		}
		return $bank;
	}



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



	static function filterCapture(array $res)
	{
		$ret = [];
		foreach ($res as $x) {
			if ($x->type->isCapture()) {
				$ret[] = $x;
			}
		}
		return $ret;
	}



	static function flatting(array $src)
	{
		$ret = [];
		foreach ($src as $x) {
			switch (True) {
				case $x->type instanceof Sequence && count($x->content) === 1 && empty($x->getName()):
					$item = reset($x->content);
					$item->start = $x->start;
					$item->end = $x->end;
					$ret[] = $item;
					break;
				case $x->type instanceof Sequence && count($x->content) === 1:
					$item = reset($x->content);
					if (empty($item->getName())) {
						$item->type = $item->type->setName($x->getName());
						$item->start = $x->start;
						$item->end = $x->end;
					}
					$ret[] = $item;
					break;
				default:
					$ret[] = $x;
			}
		}
		return $ret;
	}

}
