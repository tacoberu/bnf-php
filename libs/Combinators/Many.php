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
 * A repeating pattern without separator.
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

		// Successful match, but we're not done yet = [Token, [$name]]
		if (empty($expected) && strlen($src) > ($offset + 1)) {
			$expected = [$this->getName() => $last];
		}

		$res = Utils::filterCapture($res);
		$res = Utils::flatting($res);

		//~ return [$res, $expected];
		return [new Token($this, $res, $start, $last), $expected];
	}

}
