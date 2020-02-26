<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;


trait BaseCombinator
{

	private $name;

	private $capture;

	private $optional = False;


	function getName()
	{
		return $this->name;
	}



	function setName($val)
	{
		$inst = clone $this;
		$inst->name = $val;
		return $inst;
	}



	function setOptional()
	{
		$inst = clone $this;
		$inst->optional = True;
		return $inst;
	}



	function isCapture()
	{
		return $this->capture;
	}



	function isOptional()
	{
		return $this->optional;
	}

}
