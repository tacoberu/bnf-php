<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use RuntimeException;


class Ref
{
	public $name;
	function __construct($name)
	{
		$this->name = $name;
	}



	function requireFrom(array $bank)
	{
		if (isset($bank[$this->name])) {
			return $bank[$this->name];
		}
		throw new RuntimeException("Ref to `{$this->name}` is not found.");
	}
}
