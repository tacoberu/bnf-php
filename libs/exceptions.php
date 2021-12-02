<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use RuntimeException;


class ParseException extends RuntimeException
{

	const EMPTY_CONTENT = 10;
	const UNEXPECTED_CONTENT = 11;
	const UNEXPECTED_TOKEN = 12;

	/**
	 * @var int
	 */
	private $contentLine;

	/**
	 * @var int
	 */
	private $contentColumn;

	/**
	 * @var array<string>
	 */
	private $expectedTokens;

	/**
	 * @var string
	 */
	private $context;


	static function emptyContent() : self
	{
		return new self('Empty content.', self::EMPTY_CONTENT);
	}



	/**
	 * @param int $line
	 * @param int $col
	 * @param array<string> $expectedTokens
	 * @param string $context
	 */
	static function unexpectedToken($line, $col, array $expectedTokens, $context) : self
	{
		if (empty($expectedTokens)) {
			$msg = "Unexpected content on line $line, column $col";
			$code = self::UNEXPECTED_CONTENT;
		}
		elseif (count($expectedTokens) > 1) {
			//~ $expectedTokens = array_keys($expectedTokens);
			$last = array_pop($expectedTokens);
			$first = implode('\', \'', $expectedTokens);
			$msg = "Unexpected token on line $line, column $col: expected token '$first' or '$last'";
			$code = self::UNEXPECTED_TOKEN;
		}
		else {
			//~ $expectedTokens = array_keys($expectedTokens);
			$msg = "Unexpected token on line $line, column $col: expected token '" . implode(', ', $expectedTokens) . '\'';
			$code = self::UNEXPECTED_TOKEN;
		}

		$inst = new self($msg, $code);
		$inst->contentLine = $line;
		$inst->contentColumn = $col;
		$inst->expectedTokens = $expectedTokens;
		$inst->context = $context;
		return $inst;
	}



	/**
	 * @return int
	 */
	function getContentLine()
	{
		return $this->contentLine;
	}



	/**
	 * @return int
	 */
	function getContentColumn()
	{
		return $this->contentColumn;
	}



	/**
	 * @return array<string>
	 */
	function getExpectedTokens()
	{
		return $this->expectedTokens;
	}



	/**
	 * Fragment of code with error and nicely numbered lines.
	 * @return string
	 */
	function getContextSource()
	{
		return $this->context;
	}

}
