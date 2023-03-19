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


/**
 * Jedná o se o text, kteý začíná ", nebo ' a končí patřičným. A přičemž zohlednuje escapování uvozovek.
 */
class Text implements Combinator
{

	use BaseCombinator;


	const SLASH = "\x5c";


	/**
	 * @param ?string $name
	 * @param bool $capture
	 */
	function __construct($name, $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
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
		if ($ret = $this->match($src, $offset, $bank)) {
			return [$ret, []];
		}
		return [False, Utils::buildExpected([$this], $offset)];
	}



	/**
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank
	 * @return False|Token
	 */
	private function match($src, $offset, array $bank)
	{
		if ($offset >= strlen($src)) {
			return False;
		}
		if ($src[$offset] === '"') {
			if ($end = self::lookupQuoteIndex($src[$offset], $src, $offset)) {
				return new Token($this, self::sanitize('"', substr($src, $offset, $end - ($offset - 1))), $offset, $end + 1);
			}
		}
		if ($src[$offset] === "'") {
			if ($end = self::lookupQuoteIndex($src[$offset], $src, $offset)) {
				return new Token($this, self::sanitize("'", substr($src, $offset, $end - ($offset - 1))), $offset, $end + 1);
			}
		}
		if ($src[$offset] === '`') {
			if ($end = self::lookupQuoteIndex($src[$offset], $src, $offset)) {
				return new Token($this, self::sanitize('`', substr($src, $offset, $end - ($offset - 1))), $offset, $end + 1);
			}
		}
		return False;
	}



	/**
	 * @param string $quote
	 * @param string $src
	 * @return string
	 */
	private static function sanitize($quote, $src)
	{
		return strtr($src, [self::SLASH . $quote => $quote]);
	}



	/**
	 * @param string $quote
	 * @param string $src
	 * @param int $offset
	 * @return int|false
	 */
	private static function lookupQuoteIndex($quote, $src, $offset)
	{
		$offset += 1;
		while (($i = strpos($src, $quote, $offset)) !== False) {
			if ($src[$i - 1] !== self::SLASH) {
				return $i;
			}
			if ($src[$i - 2] === self::SLASH) {
				return $i;
			}
			$offset = $i + 1;
		}
		return False;
	}

}
