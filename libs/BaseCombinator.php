<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;


trait BaseCombinator
{

	/**
	 * @var string
	 */
	private $name;

	/**
	 * @var bool
	 */
	private $capture;

	/**
	 * @var bool
	 */
	private $optional = False;


	/**
	 * @return string
	 */
	function getName()
	{
		return $this->name;
	}



	/**
	 * @param string $val
	 * @return self
	 */
	function setName($val)
	{
		$inst = clone $this;
		$inst->name = $val;
		return $inst;
	}



	/**
	 * @return self
	 */
	function setOptional()
	{
		$inst = clone $this;
		$inst->optional = True;
		return $inst;
	}



	/**
	 * @return bool
	 */
	function isCapture()
	{
		return $this->capture;
	}



	/**
	 * @return bool
	 */
	function isOptional()
	{
		return $this->optional;
	}

}
