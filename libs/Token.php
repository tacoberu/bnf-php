<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;


class Token
{

	/**
	 * @var Combinator
	 */
	public $type;

	/**
	 * @var array<string|Token> | string
	 */
	public $content;

	/**
	 * @var int
	 */
	public $start;

	/**
	 * @var int
	 */
	public $end;


	/**
	 * @param Combinator $type
	 * @param array<string|Token> | string $content
	 * @param int $start
	 * @param int $end
	 */
	function __construct(Combinator $type, $content, $start, $end)
	{
		$this->type = $type;
		$this->content = $content;
		$this->start = $start;
		$this->end = $end;
	}



	/**
	 * @return string
	 */
	function getName()
	{
		return $this->type->getName();
	}



	/**
	 * @return bool
	 */
	function isCapture()
	{
		return $this->type->isCapture();
	}



	function __toString()
	{
		if (is_string($this->content)) {
			return $this->content;
		}
		$ret = [];
		$prev = $this->start;
		foreach ($this->content as $x) {
			if (is_string($x)) {
				$ret[] = $x;
				$prev += strlen($x) + 1;
				continue;
			}
			// start padding
			$pad = '';
			if (isset($prev) && $prev < $x->start) {
				$pad = str_repeat(' ', $x->start - $prev);
			}
			$ret[] = $pad . $x;
			$prev = $x->end;
		}

		// end padding
		if ($prev < $this->end) {
			$ret[] = str_repeat(' ', $this->end - $prev);
		}

		return implode('', $ret);
	}

}
