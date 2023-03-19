<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;


interface Combinator
{

	/**
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank A list of combinators that can be referenced.
	 * @return array{0: false|Token, 1: array<string, int>} with meening [False|Token, [$name:String => $offset:Int]]
	 */
	function scan($src, $offset, array $bank);

	/**
	 * @return string|null
	 */
	function getName();

	/**
	 * @return array<string>
	 */
	function getExpectedNames();


	/**
	 * @return bool
	 */
	function isCapture();


	/**
	 * @return bool
	 */
	function isOptional();

}
