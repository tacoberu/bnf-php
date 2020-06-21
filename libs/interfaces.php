<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;


interface Combinator
{

	/**
	 * Zjistí, zda jde matchnout číselnou hodnotu pro aktuální offset.
	 * @return [False|Token, [$name:String => $offset:Int]]
	 */
	function scan($src, $offset, array $bank);

	/**
	 * @return string|null
	 */
	function getName();

	/**
	 * @return [string]
	 */
	function getExpectedNames();

}
