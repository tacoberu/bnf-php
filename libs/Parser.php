<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use Taco\BNF\Combinator;
use Taco\BNF\Combinators\Variants;


class Parser
{

	/**
	 * @var Combinator
	 */
	private $schema;


	/**
	 * @param Combinator|array<Combinator> $schema
	 */
	function __construct($schema)
	{
		if (is_array($schema)) {
			$schema = new Variants(Null, $schema);
		}
		$this->schema = $schema;
	}



	/**
	 * @param string $src
	 * @return Token Like array of {name: string, content: string|array, start: int, end: int}
	 */
	function parse($src)
	{
		if (empty($src)) {
			throw ParseException::emptyContent();
		}
		list($node, $expected) = $this->schema->scan($src, 0, []);
		if ($node) {
			if ($node->end < strlen($src)) {
				throw self::fail($src, $node->end, array_keys($expected));
			}
			return $node;
		}
		throw self::fail($src, 0, array_keys($expected));
	}



	/**
	 * @param string $src
	 * @param int $offset
	 * @param array<string> $expectedTokens
	 * @return ParseException
	 */
	private static function fail($src, $offset, array $expectedTokens)
	{
		list($line, $col) = self::calculateCoordinates($src, $offset);

		// přidá jeden předchozí řádek
		$from = strrpos(substr($src, 0, $offset), "\n");
		if ($from > 10) {
			$from -= 10;
		}
		$from = strrpos(substr($src, 0, $from), "\n");
		$first = $line - substr_count(substr($src, $from, $offset), "\n");
		return ParseException::unexpectedToken($line, $col, $expectedTokens
			, self::formatContext(substr($src, $from, 255 * 8), $first, $line, $col));
	}



	/**
	 * Returns position of token in input string.
	 * @param string $src
	 * @param int $offset
	 * @return array<int> of [line, column]
	 */
	private static function calculateCoordinates($src, $offset)
	{
		$src = substr($src, 0, $offset);
		return [
			substr_count($src, "\n") + 1,
			$offset - strrpos("\n" . $src, "\n") + 1
		];
	}



	/**
	 * @param string $src
	 * @param int $first
	 * @param int $line
	 * @param int $col
	 * @return string
	 */
	private static function formatContext($src, $first, $line, $col)
	{
		$xs = [];
		foreach (explode("\n", $src) as $i => $x) {
			$xs[] = sprintf('%' . ($first + 1) . 'd > %s', $first + $i, $x);
			if ($first + $i == $line) {
				$xs[] = sprintf('%' . (($first + 1) + $col + 3) . 's', '---^'); // ——— @TODO
			}
		}
		if (count($xs) > 20) {
			$xs = array_slice($xs, 0, 20);
		}
		return implode("\n", $xs);
	}

}
