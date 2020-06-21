<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;


class Token
{

	public $type, $content, $start, $end;


	function __construct(Combinator $type, $content, $start, $end)
	{
		$this->type = $type;
		$this->content = $content;
		$this->start = $start;
		$this->end = $end;
	}



	function getName()
	{
		return $this->type->getName();
	}



	function __toString()
	{
		if (is_string($this->content)) {
			return $this->content;
		}
		$ret = [];
		foreach ($this->content as $x) {
			$pad = '';
			if (isset($prev) && $prev < $x->start) {
				$pad = str_repeat(' ', $x->start - $prev);
			}
			$ret[] = $pad . $x;
			$prev = $x->end;
		}
		return implode('', $ret);
	}

}
