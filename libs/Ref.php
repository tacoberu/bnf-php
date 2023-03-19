<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use RuntimeException;


class Ref
{
	/**
	 * @var string
	 */
	public $name;


	/**
	 * @param string $name
	 */
	function __construct($name)
	{
		$this->name = $name;
	}



	/**
	 * @param array<string, Token> $bank
	 * @return Token
	 */
	function requireFrom(array $bank)
	{
		if (isset($bank[$this->name])) {
			return $bank[$this->name];
		}
		throw new RuntimeException("Ref to `{$this->name}` is not found.");
	}



	/**
	 * @return ?string
	 */
	function getName()
	{
		return $this->name;
	}



	/**
	 * @return array<string>
	 */
	function getExpectedNames()
	{
		return [$this->name];
	}
}
