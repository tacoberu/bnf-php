<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF\Combinators;

use Taco\BNF\Token;
use Taco\BNF\Ref;
use Taco\BNF\Parser;
use PHPUnit\Framework\TestCase;


/**
 * https://github.com/projectfluent/fluent/blob/master/spec/fluent.ebnf
 *
 * Odsazení se počítá tak, že se vyřízne blok. Blok začíná offsetem. Konec
 * se spočítá tak, že od ofsetu se zpětně vyhledá začátek. Od začátku se určí
 * odsazení. Cokoliv má větší odsazení, patří do bloku. Pokud je to menší nebo
 * rovno, to už je konec.
 */
class IndentTest extends TestCase
{


	/**
	 * @dataProvider dataSliceBlock
	 */
	function testSliceBlock($src, $offset, $expected)
	{
		$this->assertSame($expected, substr(Indent::sliceBlock($src, $offset, Indent::calculateIndent($src, $offset)), $offset));
	}



	function dataSliceBlock()
	{
		return [
			['', 0, ''],
			[' ', 0, ' '],
			[' alef', 0, ' alef'],
			['  alef', 0, '  alef'],
			[" alef\nbet", 0, " alef"],
			[" alef\n bet", 0, " alef"],
			[" alef\n  bet", 0, " alef\n  bet"],
			[" alfa\n  beta\n", 0, " alfa\n  beta"],
			[" alfa\n  beta\n\n", 0, " alfa\n  beta"],
			[" alfa\n  beta\n\ngama", 0, " alfa\n  beta"],
			[" alfa\n  beta\n\n gama", 0, " alfa\n  beta"],
			[" alfa\n  beta\n\n  gama", 0, " alfa\n  beta\n\n  gama"],
			[" alfa\n  beta\n\n    gama", 0, " alfa\n  beta\n\n    gama"],
			[" alfa\n  beta\n\n    gama\n", 0, " alfa\n  beta\n\n    gama"],
			[" alfa\n   beta\n\n gama", 0, " alfa\n   beta"],
			[" alfa\n   beta\n  gama", 0, " alfa\n   beta\n  gama"],
			[" alfa\n   beta\n\n  gama", 0, " alfa\n   beta\n\n  gama"],
			[" alfa\n beta\n\n gama", 0, " alfa"],
			["  alfa\n  beta\n\n  gama", 0, "  alfa"],
			["  alfa\n   beta\n\n  gama", 0, "  alfa\n   beta"],
			["
	[a] Alef
	[b] Beth {quatre2}
	[c] Gimed {trois3}
	[d] Quatre {une4 ->
		[une] Lorem ipsum doler ist
		[deux] Rien
	}", 6, "Alef"],
			["
	[a] Alef
	[b] Beth {quatre2}
	[c] Gimed {trois3}
	[d] Quatre {une4 ->
		[une] Lorem ipsum doler ist
		[deux] Rien
	}", 12, "[b] Beth {quatre2}"],
			["
	[a] Alef
	[b] Beth {quatre2}
	[c] Gimed {trois3}
	[d] Quatre {une4 ->
		[une] Lorem ipsum doler ist
		[deux] Rien
	}", 15, " Beth {quatre2}"],
			["
	[a] Alef
	[b] Beth {quatre2}
	[c] Gimed {trois3}
	[d] Quatre {une4 ->
		[une] Lorem ipsum doler ist
		[deux] Rien
	}", 56, "Quatre {une4 ->\n\t\t[une] Lorem ipsum doler ist\n\t\t[deux] Rien\n\t}"],
			['shared-photos = Une
twoWords =
	Lorem ipsum doler ist.
slovo = Nějaký text
	text pokračuje s odsazením
	a další řádka.

names =
	Une
	 Deux

	Trois
', 63, 'Nějaký text
	text pokračuje s odsazením
	a další řádka.'],
			['shared-photos = Une
twoWords =
	Lorem ipsum doler ist.
slovo = Nějaký text
	text pokračuje s odsazením
	a další řádka.

names =
	Une
	 Deux

	Trois
', 135, "\n\tUne\n\t Deux\n\n\tTrois"],
		];
	}



	/**
	 * @dataProvider dataCalculateIndent
	 */
	function testCalculateIndent($src, $offset, $expected)
	{
		$this->assertSame($expected, Indent::calculateIndent($src, $offset));
	}



	function dataCalculateIndent()
	{
		return [
			['', 0, null],
			['abc', 0, null],
			["abc\ndef", 0, null],
			["abc\ndef", 1, null],
			["abc\ndef", 2, null],
			["abc\ndef", 3, null],
			["abc\ndef", 4, null],
			["abc\ndef", 5, null],
			[" abc\ndef", 0, ' '],
			[" abc\ndef", 1, ' '],

			[" abc\ndef", 2, ' '],
			[" abc\ndef", 3, ' '],
			[" abc\ndef", 4, ' '],
			[" abc\ndef", 5, null],
			[" abc\ndef", 6, null],
			[" abc\n def", 5, ' '],
			[" abc\n def", 7, ' '],
			[" abc\n def", 8, ' '],
			[" abc\n\tdef", 8, "\t"],
			[" abc\n\t\ndef", 8, null],

			["  alfa\n  beta\n\n  gama", 0, '  '],
			["  trois\n  deux\n  une\n	", 8, '  '],
			["  trois\n  deux\n  une\n	", 15, '  '],
			["null = une
		deux
		trois {quatre ->
			[cinq] six
			[sept] huit
		} neuf.

dix = onze
		", 35, "\t\t"],
			["
			[cinq] six
			[sept] huit", 1, "\t\t\t"],

		];
	}



	/**
	 * @dataProvider dataBackwardNewLine
	 */
	function testBackwardNewLine($src, $offset, $expected)
	{
		$this->assertSame($expected, Indent::backwardNewLine($src, $offset));
	}



	function dataBackwardNewLine()
	{
		return [
			['', 0, False],
			['abc', 0, False],
			["abc\n", 0, False],
			["abc\ndef", 0, False],
			["abc\ndef", 1, False],
			["abc\ndef", 2, False],
			["abc\ndef", 3, False],
			["abc\ndef", 4, 3],
			["abc\ndef", 5, 3],
			["abc\ndef", 6, 3],
			["abc\ndef", 7, 3],
			["abc\ndef", 8, 0],
			["abc\ndef", 9, 0],
			["abc\ndef\nefg\nhij", 7, 3],
			["abc\ndef\nefg\nhij", 9, 7],
			["abc\ndef\nefg\nhij", 12, 11],
			["abc\ndef\nefg\n\nhij", 12, 11],
			["abc\ndef\nefg\n\nhij", 13, 12],
			["abc\ndef\nefg\n\nhij", 14, 12],
			["  alfa\n  beta\n\n  gama", 0, False],
			["  alfa\n  beta\n\n  gama", 5, False],
			["  alfa\n  beta\n\n  gama", 6, False],
			["  alfa\n  beta\n\n  gama", 7, 6],
			["\nalfa\n  beta\n\n  gama", 1, 0],
			["a\nalfa\n  beta\n\n  gama", 2, 1],
		];
	}



	function testName()
	{
		$this->assertSame('num', (new Indent('num', new Whitechars(Null)))->getName());
		$this->assertSame('xum', (new Indent('xum', new Whitechars(Null)))->getName());
	}



	function testCapture()
	{
		$this->assertTrue((new Indent('num', new Whitechars(Null)))->isCapture());
		$this->assertFalse((new Indent('num', new Whitechars(Null), [], False))->isCapture());
	}



	/**
	 * @dataProvider dataCorrect
	 */
	function testCorrect($def, $src, $ast)
	{
		list($token, $expected) = $def->scan($src, 0, []);
		$this->assertEquals($ast, self::unbox($token));
		$this->assertSame([], $expected);
		(new Parser($def))->parse($src);

	}



	function dataCorrect()
	{
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~\n+~'], False);
		$col = new Pattern('col', ['~[a-zA-Z\-]+\:~']);
		$text2 = new Pattern('text', ['~.*~s']);
		$text = new Indent('text', $text2);
		$element = new Sequence('node', [
			$col, $sep, $text
		]);
		$def1 = new Variants(Null, [
			$element, $nl
		]);
		$choiceOpt = $this->getChoiceOption();


		return [
			[$def1, 'jedna: une et une
	et deux et trois

dva: deux
', [				['jedna:', "une et une
	et deux et trois"],
					['dva:', 'deux'],
				]],
			[$def1, "jedna: une


dva: deux
", [
					['jedna:', "une"],
					['dva:', 'deux'],
					]
				],
			[$def1, "jedna: une et une
	et deux et trois
	Jak se to píše

	Přidáme mezeru
	  Odsazení.

	Poslední řádka.

dva: deux
",
					[['jedna:', "une et une
	et deux et trois
	Jak se to píše

	Přidáme mezeru
	  Odsazení.

	Poslední řádka."]
					,['dva:', 'deux']
						],
				],
			[$choiceOpt, "*[ trois  ] Lorem ipsum doler ist
		psům
		kočkám
	", ['*', '[ trois  ] ', "Lorem ipsum doler ist\n\t\tpsům\n\t\tkočkám\n\t"],
				],
		];
	}



	private function getFluentDef2()
	{
		$symbol = '[a-z\-][a-z0-9]*';
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~\n+~'], False);
		$text = new Pattern('text', ['~[^\{]+~s']);
		$varref = new Pattern('varref', ['~\{' . $symbol . '\}~i']);

		$option = new Indent('option-x', new Sequence('option', [
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('opt', ['~\[\s*' . $symbol . '\s*\]~']),
			new Pattern(Null, ['~[ \t]*~'], False),
			//~ new Ref('message'),
			//~ $text, // ?
			new Variants('t', [
				new Ref('choice'),
				$text,
				$varref,
				//~ new Pattern('text', ['~.+~s']),
			]),
		]));
		$options = new Variants('choice-option', [
			$option,
			$nl,
		]);
		$choice = new Sequence('choice', [
			new Pattern('choice-start', ['~\{\s*~'], False),
			new Pattern('choice-id', ['~' . $symbol . '~']),
			$sep,
			new Match('assign', ['->'], False),
			new Indent('choice-content', new Sequence(Null, [
				$nl,
				$options,
			])),
			new Pattern('choice-end', ['~\s*\}~'], False)
		]);
		$msg = new Sequence('root-node', [
			new Pattern('id', ['~' . $symbol . '~']),
			$sep,
			new Match('assign', ['='], False),
			$sep,
			new Indent('root-node-message', new Variants('message', [
				$choice,
				$text,
				$varref,
			])),
		]);
		$def = new Variants('x-root', [
			$msg,
			$nl,
		]);
		return $def;
	}



	private function getFluentDef1()
	{
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~\n+~'], False);
		$option = new Indent('a', new Sequence('b', [
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('opt', ['~\[\s*[a-z]+\s*\]~']),
			new Pattern(Null, ['~\s*~'], False),
			new Pattern('text', ['~.+~s'])
		]));
		$options = new Variants('choice-option', [
			$option,
			$nl,
		]);
		$choice = new Sequence('choice', [
			new Match('abc', ['{trois ->']),
			$nl,
			$options,
			new Pattern('text', ['~\s*\}~'])
		]);
		$text = new Pattern('text', ['~[^\{]+~s']);
		$varref = new Pattern('varref', ['~\{[a-z]+\}~']);
		$value = new Variants('message', [
			$text,
			$choice,
			$varref,
		]);
		$msg = new Sequence('msg', [
			new Pattern('id', ['~[a-z]+~']),
			$sep,
			new Match('assign', ['='], False),
			$sep,
			new Indent(Null, $value),
		]);
		$def = new Variants(Null, [
			$msg,
			$nl,
		]);
		return $def;
	}



	private function getChoiceOption()
	{
		return new Sequence('choice-option', [
			new Match('default?', [' ', '*']),
			new Pattern('opt', ['~\[\s*[a-zA-Z\-]+\s*\]\s*~']),
			new Indent(Null, new Pattern('text', ['~.+~s'])),
		]);
	}



	function testSample1()
	{
		// Očekávaný vzor první řádek, a všechno ostatní je odsazené. Protože ale není řečeno, co se má dělat dál, tak to skončí před 2key.
		$parse = new Indent('msg',
			new Any('val'),
		);
		$src = "1abcdefghijklmno: one
2key: jedna
3key: une
	deux trois

";
		list($token, $expected) = $parse->scan($src, 0, []);
		$this->assertEquals('1abcdefghijklmno: one', self::unbox($token));
		$this->assertEquals([], $expected);

		list($token, $expected) = $parse->scan($src, 5, []);
		$this->assertEquals('efghijklmno: one', self::unbox($token));
		$this->assertEquals([], $expected);

		list($token, $expected) = $parse->scan($src, 21, []);
		$this->assertFalse(self::unbox($token));
		$this->assertEquals(['val' => 21], $expected);

		list($token, $expected) = $parse->scan($src, 22, []);
		$this->assertEquals('2key: jedna', self::unbox($token));
		$this->assertEquals([], $expected);

		list($token, $expected) = $parse->scan($src, 34, []);
		$this->assertEquals("3key: une\n\tdeux trois", self::unbox($token));
		$this->assertEquals([], $expected);

		$src = "1abcdefghijklmno: one
2key: jedna
3key: une
	*deux trois
	 quatre cinq
	 six
";
		list($token, $expected) = $parse->scan($src, 34, []);
		$this->assertEquals("3key: une\n\t*deux trois\n\t quatre cinq\n\t six", self::unbox($token));
		$this->assertEquals([], $expected);
	}



	function _______testDevF()
	{
		$skipIndent = [['{', '}']];
		$symbol = '[a-z\-][a-z0-9]*';
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~\n+~'], False);
		$textElement = new Pattern('text-element', ['~[^\{\}]+~s']);
		$variableReference = new Pattern('variable-reference', ['~\{' . $symbol . '\}~i']);
		$option = new Sequence('select-option', [
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('option-identifier', ['~\[\s*' . $symbol . '\s*\]~']),
			new Pattern(Null, ['~[ \t]*~'], False),
			new Indent(Null, new Variants('placeable', [
				$variableReference,
				new Ref('select-expression'),
				$textElement,
			])),
		]);
		$selectOptions = new Variants('select-options', [
			$option,
			$nl,
		]);
		$selectExpression = new Sequence('select-expression', [
			new Pattern('select-start', ['~\{\s*~'], False),
			new Pattern('select-identifier', ['~' . $symbol . '~']),
			$sep,
			new Match('assign', ['->'], False),
			new Indent(Null, new Sequence(Null, [
				$nl,
				$selectOptions,
			]), $skipIndent),
			new Pattern('select-end', ['~\s*\}~'], False)
		]);
		$messageContent = new Variants('placeable', [
			$variableReference,
			$selectExpression,
			$textElement,
		]);
		$message = new Sequence('message', [
			new Pattern('identifier', ['~' . $symbol . '~']),
			$sep,
			new Match('assign', ['='], False),
			$sep,
			new Indent('pattern', $messageContent, $skipIndent),
		]);
		$xroot = new Variants(Null, [
			$message,
			$nl,
		]);

		$src = "
			[a] Alef
			[b] Beth {quatre2}
			[c] Gimed {trois3}
			[d] Quatre {une4 ->
				[une] Lorem ipsum doler ist
				[deux] Rien
			}
			[e] Elementary ";

		list($token, $expected) = $selectOptions->scan($src, 1, [
			'messagee' => $message,
			'message-content' => $messageContent,
			'select-expression' => $selectExpression,
			'select-option' => $selectOptions,
		]);
		//~ print_r(self::unbox($token));
		$this->assertEquals([
			["\t\t\t", '[a]', ['Alef']],
			["\t\t\t", '[b]', ['Beth ', '{quatre2}']],
			["\t\t\t", '[c]', ['Gimed ', '{trois3}']],
			["\t\t\t", '[d]', ['Quatre ', [
				'une4',
				[[
					["\t\t\t\t", '[une]', ['Lorem ipsum doler ist']],
					["\t\t\t\t", '[deux]', ["Rien"]],
				]],
			]]],
			["\t\t\t", '[e]', ['Elementary ']],
		], self::unbox($token));
		$this->assertSame([], $expected);

		return;
/*
		$src = "			[d] Quatre {une4 ->
				[une] Lorem ipsum doler ist
				[deux] Rien";
		list($token, $expected) = $choice->scan($src, 7, [
			'x-root' => $xroot,
			'root-node' => $msg,
			'message' => $message,
			'choice' => $choice,
			'choice-option' => $choiceoptions,
			'option' => $option,
			't' => $option,
		]);
		dump(self::unbox($token));
		dump($expected);

		$src = "
				[une] Lorem ipsum doler ist
				[deux] Rien";
		list($token, $expected) = $choiceContent->scan($src, 0, [
			'x-root' => $xroot,
			'root-node' => $msg,
			'message' => $message,
			'choice' => $choice,
			'choice-option' => $choiceoptions,
			'option' => $option,
			't' => $option,
		]);
		dump(self::unbox($token));
		dump($expected);
		*/
	}



	private static function unbox($token)
	{
		if (False === $token) {
			return False;
		}
		if ( ! isset($token->content)) {
//~ dump($token);
die('=====[' . __line__ . '] ' . __file__);
		}
		if (is_array($token->content)) {
			$xs = [];
			foreach ($token->content as $x) {
				$xs[] = self::unbox($x);
			}
			return $xs;
		}
		if (is_string($token->content)) {
			return $token->content;
		}
//~ dump($token->content);
die('=====[' . __line__ . '] ' . __file__);
	}



	function _______testDevA()
	{
		$src = "jedna = une et: une
	et deux et {trois ->
		[a] jedna
		[b] dva
	} Jak se to píše

	Přidáme mezeru
	  Odsazení.

	Poslední řádka.

dva: deux
tri: {tris}
";

		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~\n+~'], False);
		$col = new Pattern('col', ['~[a-zA-Z\-]+\:~']);
		$text = new Pattern('text', ['~[^\{]*~s']);
		$var = new Sequence('var', [
			new Pattern('{', ['~\{~'], False),
			new Pattern('var', ['~[a-zA-Z\-]+~']), // bylo by pěkné, když by se rozpustila do rodičovského uzlu
			new Pattern('}', ['~\}~'], False),
		]);
		$choice = new Sequence('choice', [
			new Pattern('{', ['~\{~'], False),
			new Pattern('var', ['~[a-zA-Z\-]+~']),
			new Pattern('->', ['~\s+\-\>\s+~'], False),
			new Pattern('any', ['~[^\}]*\}~']),
		]);
		$choice2 = new Sequence('choice', [
			new Pattern('{', ['~\{~'], False),
			new Pattern('var', ['~[a-zA-Z\-]+~']),
			new Pattern('->', ['~\s+\-\>\s+~'], False),
			new Indent('choises', new Pattern('xc', ['~.*~'])),
/*
			new Indent('choises', new Variants('x-2', [
				new Pattern('xc', ['~[a-zA-Z\-]+~']),
				//~ $nl,
			])), //*/
			new Pattern('}', ['~\}~']),
		]);
		$content = new Variants('content', [
			$var,
			$choice2,
			$text,
		]);
		$element = new Sequence('element', [
			$col,
			$sep,
			new Indent('_a', $content)
		]);
		$parse = new Variants('root', [
			$element,
			$nl
		]);

		list($token, $expected) = $parse->scan($src, 0, []);
		dump($expected);
print_r(self::unbox($token));
		/*
		$this->assertEquals([
			['jedna:', "une et: une
	et deux et {trois}
	Jak se to píše

	Přidáme mezeru
	  Odsazení.

	Poslední řádka."],
			['dva:', 'deux'],
		], self::unbox($token));*/
		//~ $this->assertEquals([], $expected);
		//~ (new Parser($parse))->parse($src);
	}



	function _______testDevB()
	{
		$text = new Indent(Null,
			new Pattern(Null, [
				'~\d[a-zA-Z\d\s\-\.]+~',
			])
		);

		$choice2 = new Sequence('choice', [
			new Pattern('{', ['~\{~'], False),
			new Pattern('var', ['~[a-zA-Z\-]+~']),
			new Pattern('->', ['~\s+\-\>\s+~'], False),
			new Indent('choises', new Pattern('xc', ['~.*~'])),
/*
			new Indent('choises', new Variants('x-2', [
				new Pattern('xc', ['~[a-zA-Z\-]+~']),
				//~ $nl,
			])), //*/
			new Pattern('}', ['~\}~']),
		]);


		$src = "{trois ->
		jedna
		dva
	}";
		(new Parser($choice2))->parse($src);
	}



	function _______testDevT()
	{
		$src = "	[ trois  ] Lorem ipsum doler ist
		i psům i kočkám.
	[jedna] a
	[dva]   b
	";
		$src = "  [ trois  ] Lorem ipsum doler ist
    i psům i kočkám.
  [jedna] a
  [dva]   b
";
		$nl = new Pattern(Null, ['~\n+~'], False);
		$def = new Indent(Null, new Pattern('text', ['~.+~s']));
		$def = new Indent(Null, new Variants(Null, [
			new Pattern('text', ['~.+~s']),
			$nl,
		]));
		$option = new Indent('a', new Pattern('text', ['~.+~s']));
		$option = new Indent('a', new Sequence('b', [
			//~ (new Whitechars(Null, False))->setOptional(),
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('opt', ['~\[\s*[a-z]+\s*\]~']),
			new Pattern(Null, ['~\s*~'], False),
			new Pattern('text', ['~.+~s'])
		]));
		$def = new Variants('r', [
			$option,
			$nl,
			//~ new Indent('b', new Pattern('text', ['~.+~s'])),
		]);
		$token = $def->scan($src, 0, [])[0];
		//~ dump($token->content);
		dump(self::unbox($token));
		$this->assertCount(3, self::unbox($token));

		//~ $token = (new Parser($def))->parse($src);
		//~ dump($token);
	}



	function _______testDevR()
	{
		/*
		$src = "symbol = Prev {trois ->\n    [ trois  ] Lorem ipsum doler ist
      i psům i kočkám.

      a jak to jde?
       nějaké odsazení?

      mezery?


   *[jedna] a
    [dva]   b
  } nějaký další texty, {volba} a závěr.

  ";
  */
		$src = "abc = Text jak se daří {prom} exn
		druhá řádka. {promx} a další
		řádka {quatre1 ->
			[a] A
			[b] B {quatre2}
			[c] C {trois3}
			[d] Quatre {une4 ->
				[une] Lorem ipsum doler ist
				[deux] Rien
			}
		} na závěr.

def = Ahoj
		";
		$src = "abc = First line
		druhá řádka
		třetí řádka {quatre1 ->
			[a] Alef
			[b] Beth {quatre2}
			[c] Gimed {trois3}
			[d] Quatre {une4 ->
				[une] Lorem ipsum doler ist
				[deux] Rien
			}
		} end fini.

def = Ahoj
		";
		$src = "abc = First line
		druha radka
		treti radka {quatre1 ->
			[a] Alef
			[b] Beth {quatre2}
			[c] Gimed {trois3}
			[d] Quatre {une4 ->
				[une] Lorem ipsum doler ist
				[deux] Rien
			}
		} end fini.

def = Ahoj
		";

		//~ (new Parser($def))->parse($src);die('=====[' . __line__ . '] ' . __file__);
		list($token, $expected) = $this->getFluentDef2()->scan($src, 0, []);
print_r(self::unbox($token));
dump($expected);
	}



	function _______testDevS()
	{
		$src = "abc = Text jak se daří {prom} exn
		druhá řádka. {promx} a další
		řádka {quatre1 ->
			[a] A
			[b] B {quatre2}
			[c] C {trois3}
			[d] Quatre
		} na závěr.

def = Ahoj
		";
/*
		$src2 = "null = une
		deux
		trois {quatre ->
			[cinq] six
			[sept] huit
		} neuf.

dix = onze
		";
*/
	}



	function _______testDevX()
	{
		$src = "";
		$src = "  [ trois  ] Lorem ipsum doler ist
    i psům i kočkám.
  [jedna] a
  [dva]   b
	";
		$src2 = "  une\n  deux\n  trois\n	";
		$src2 = "  trois\n  deux\n  une\n	";
		$src = "null = une
		deux
		trois {quatre ->
			[cinq] six
			[sept] huit
		} neuf.

dix = onze
		";

/*
		$offset = 0;
		$offset = 59;
		$offset = 7;
		//~ $offset = 35;
		//~ $offset2 = strlen("  trois\n  deux") + 1;
		//~ dump($offset);
		//~ dump($offset + 20 + 7);
		//~ dump(substr($src, $offset + 20 + 7));
		$offset = 36;
		//~ dump(substr($src, $offset));
		//~ dump(Indent::calculateIndent($src, $offset));
		$this->assertSame("\t\t", Indent::calculateIndent($src, $offset));
		//~ dump(Indent::sliceBlock($src, $offset));
		*/
		$src = "
			[cinq] six
			[sept] huit";
		$offset = 1;
		//~ dump(Indent::sliceBlock($src, $offset));
	}



	function _______testDevY()
	{
		$parse = new Indent('box',
			new Sequence('q', [
				new Pattern('col', ['~\d?[a-zA-Z\-]+\:~']),
				new Whitechars(Null, False),
				//~ new Indent('box', new Pattern('content', ['~.*~s'])),
				new Indent('box', new OneOf(Null, [
					new Pattern('content', ['~.*~s']),
				])),//*/
			])
		);
		$src = "1abcdefghijklmno
	2key:
		3val: ABCDEFGHIJKLMN
			4val: OPQRrSsTTUVWXYZ
				7a.a.a
		8.b.b.
			4val: opo

	6b-x-x-x-x-x-b-b
		9.d.d.
		.a.a.
		.b.b.
";

		$src = "1abcdefghijklmno
	2key: jedna
		dva
		tří
		3key: une

";
		$src = "1abcdefghijklmno:
    2key: jedna
        dva
       tří
         3key: une

";


		list($token, $expected) = $parse->scan($src, 17, []);
		//~ $this->assertSame("2pqrrssttuvwxyzz\n    3ABCDEFGHIJKLMN\n      4OPQRrSsTTUVWXYZ\n      6b-x-x-x-x-x-b-b\n    7a.a.a", (string)$token);
		dump(self::unbox($token), $expected);
	}



	/**
	 * @TODO
	 * Je třeba vyřešit jak s tou hvězdičkou - kazí to odsazování.
	 */
	function _______testDevZ()
	{
		$src = "{trois ->\n  [ trois  ] Lorem ipsum doler ist
    i psům i kočkám.

    a jak to jde?
     nějaké odsazení?

    mezery?


  *[jedna] a
  [dva]   b
";

		$nl = new Pattern(Null, ['~\n+~'], False);
		$option = new Indent('a', new Sequence('b', [
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('opt', ['~\[\s*[a-z]+\s*\]~']),
			new Pattern(Null, ['~\s*~'], False),
			new Pattern('text', ['~.+~s'])
		]));
		$choice = new Variants('choice', [
			$option,
			$nl,
		]);
		$def = new Sequence(Null, [
			new Match('abc', ['{trois ->']),
			$nl,
			$choice,
		]);

		list($token, $expected) = $def->scan($src, 0, []);
		$this->assertEquals([
			'{trois ->',
			[
				['  ', '[ trois  ]', "Lorem ipsum doler ist\n    i psům i kočkám.\n\n    a jak to jde?\n     nějaké odsazení?\n\n    mezery?"],
				['  *', '[jedna]', "a"],
				['  ', '[dva]', "b"],
			]
		], self::unbox($token));
		$this->assertEquals([], $expected);
	}



	function _______testSample2()
	{
		$src = "Prev {trois ->\n  [ trois  ] Lorem ipsum doler ist
    i psům i kočkám.

    a jak to jde?
     nějaké odsazení?

    mezery?


 *[jedna] a
  [dva]   b
} nějaký další texty, {volba} a závěr.
";

		$nl = new Pattern(Null, ['~\n+~'], False);
		$option = new Indent('a', new Sequence('b', [
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('opt', ['~\[\s*[a-z]+\s*\]~']),
			new Pattern(Null, ['~\s*~'], False),
			new Pattern('text', ['~.+~s'])
		]));
		$options = new Variants('choice-option', [
			$option,
			$nl,
		]);
		$choice = new Sequence('choice', [
			new Match('abc', ['{trois ->']),
			$nl,
			$options,
			new Pattern('text', ['~\s*\}~'])
		]);
		$text = new Pattern('text', ['~[^\{]+~s']);
		$varref = new Pattern('varref', ['~\{[a-z]+\}~']);
		$def = new Variants('message', [
			$text,
			$choice,
			$varref,
		]);

		list($token, $expected) = $def->scan($src, 0, []);
		$this->assertEquals([
			'Prev ',
			[
				'{trois ->',
				[
					[
						'  ',
						'[ trois  ]',
						"Lorem ipsum doler ist\n    i psům i kočkám.\n\n    a jak to jde?\n     nějaké odsazení?\n\n    mezery?\n\n\n *[jedna] a",
					],
					[
						'  ',
						'[dva]',
						'b',
					],
				],
				'}',
			],
			' nějaký další texty, ',
			'{volba}',
			" a závěr.\n",
		], self::unbox($token));
		$this->assertSame([], $expected);
	}

}
