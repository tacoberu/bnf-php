<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Utils;
use Taco\BNF\BaseCombinator;
use Taco\BNF\Combinator;


/**
 * Hlídá, jestli se snížilo odsazení. To znamená:
 * - řádka musí mít víc znaků, než řádka před odsazením. Klidně jeden znak.
 * - všechny řádky, které jsou odsazené o více jak jeden znak, než kolik bylo před Indent se počítá za obsah. Takže klidně mohou bejt různě cikcak.
 * - můžou být řečené výjimky, které se do odsazení započítávaj přestože jsou kratšé. Typycky komentáře, prázdná řádka, etc.
 */
class Indent implements Combinator
{

	use BaseCombinator;

	private $parser;
	private $skipIndent;


	/**
	 * @param string $name
	 * @param bool $capture
	 */
	function __construct($name, Combinator $parser, array $skipIndent = [], $capture = True)
	{
		$this->name = $name;
		$this->capture = $capture;
		$this->parser = $parser;
		$this->skipIndent = $skipIndent;
	}



	/**
	 * @return array<string>
	 */
	function getExpectedNames()
	{
		if (empty($this->name)) {
			return [];
		}
		return [$this->name];
	}



	/**
	 * @param string $src
	 * @param int $offset
	 * @param array<string, Combinator> $bank
	 * @return array{0: false|Token, 1: array<string, int>}
	 */
	function scan($src, $offset, array $bank)
	{
		$bank = Utils::addToBank($bank, $this);
		// Uplně humpolácky to můžeme udělat tak, že si vyřízneme kus $src, a pak ho profiltrujeme
		// a budeme hledat konec - to znamená, kde je odsazení menší jak $indent.
		// To pak vrátíme, a pustíme na tom $parser.
		$indent = self::calculateIndent($src, $offset);
		$sub = self::sliceBlock($src, $offset, $indent);
		list($ast, $expected) = $this->parser->scan($sub, $offset, $bank);
		if ($ast) {
			return [new Token($ast->type,
					$ast->content,
					$ast->start,
					$ast->end
				), $expected];
		}
		return [False, $expected];
	}



	/**
	 * Hledáme místo, které je kratší = začíná newhite znakem, než odsazení
	 * Pokud najde komentář, tak přeskočit na konec komentáře.
	 * Pokud najde { tak pokračovat až na konec bloku
	 */
	/* private*/  static function sliceBlock($src, $offset, $indent)
	{
		$endIndex = self::lookupEndOfBlock($indent, $src, $offset);
		// @TODO Mám tu zadrátováno bloky { a }.
		list($startBlockIndex, $endBlockIndex) = Utils::lookupBlock('{', '}', $src, $offset);
		if ($startBlockIndex !== False && $startBlockIndex < $endIndex) {
			$endIndex = self::lookupEndOfBlock($indent, $src, $endBlockIndex);
		}

		if ($endIndex !== False) {
			return rtrim(substr($src, 0, $endIndex + 1), "\n");
		}

		// vše až do konce
		return rtrim($src, "\n");
	}



	private static function lookupEndOfBlock($indent, $src, $offset)
	{
		// začátek řádku
		if (preg_match('~\n[^\s]~s', $src, $out1, PREG_OFFSET_CAPTURE, $offset)) {
			$out1 = reset($out1);
		}
		// odskočení zpět
		if (preg_match('~\n' . $indent . '[^\s]~s', $src, $out2, PREG_OFFSET_CAPTURE, $offset)) {
			$out2 = reset($out2);
		}

		if (count($out1) && count($out2)) {
			if ($out1[1] < $out2[1]) {
				$out = $out1;
			}
			else {
				$out = $out2;
			}
			return $out[1];
		}
		elseif (count($out1)) {
			return $out1[1];
		}
		elseif (count($out2)) {
			return $out2[1];
		}
		return False;
	}



	/**
	 * Zjistíme, jak velké je odsazení aktuálního bloku. Klidně i když je
	 * zpracované. Ale očekáváme, že následující řádka musí být odsazená více.
	 * @return string
	 */
	/*private*/ static function calculateIndent($src, $offset)
	{
		if ($offset > 0) {
			$nli = self::backwardNewLine($src, $offset);
			if (($nli2 = strpos($src, "\n", $offset)) === False) {
				$nli2 = strlen($src);
			}
			// chunk od začátku řádky
			$src = substr($src, ($nli !== false ? $nli + 1 : 0), ($nli2 - $nli) - 1);
		}

		if (preg_match('~^[ \t]+~', $src, $out)) {
			return $out[0];
		}
		return Null;
	}



	/**
	 * Jedem pozpátku a hledám první předchozí konec řádku.
	 * @return int
	 */
	/*private*/ static function backwardNewLine($src, $offset)
	{
		if (0 == $offset) {
			return False;
		}
		if ($offset > strlen($src)) {
			return 0;
		}
		static $capacity = 5;
		$index = $capacity;
		if ($offset < $index) {
			$chunk = substr($src, 0, $offset);
			return strrpos($chunk, "\n");
		}
		do {
			$chunk = substr($src, $offset - $index, $capacity);
			if (($i = strrpos($chunk, "\n")) !== False) {
				return ($offset - ($index - $i));
			}
			$index += $capacity;
		} while ($index < $offset);

		$chunk = substr($src, 0, $offset);
		return strrpos($chunk, "\n");
	}
}
