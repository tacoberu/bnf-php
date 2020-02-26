<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use PHPUnit_Framework_TestCase;
use LogicException;
use Taco\BNF\Combinators\Whitechars;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Numeric;
use Taco\BNF\Combinators\Match;
use Taco\BNF\Combinators\Sequence;
use Taco\BNF\Combinators\Variants;
use Taco\BNF\Combinators\OneOf;
use Taco\BNF\Combinators\Indent;
use Taco\BNF\Combinators\Any;
use Taco\BNF\Combinators\FluentSpecIndent;
use Taco\BNF\Combinators\FluentSelectExpression;


// demonstrace
class ExamplesTest extends PHPUnit_Framework_TestCase
{


	/**
	 * @dataProvider dataBoolExpressionTree
	 */
	function testBoolExpressionTree($src)
	{
		$sep = new Whitechars(Null, False);
		$bool = new Pattern('AND/OR', ['~\s+AND\s+~i', '~\s+OR\s+~i']);
		$not = new Pattern('NOT', ['~\s*NOT\s+~i']);
		$op = new OneOf('operator (=, !=, IS, LIKE, IN, etc)', [
				new Pattern(Null, [
					'~NOT\s+LIKE~i',
					'~NOT\s+IN~i',
				]),
				new Match(Null, [
					'LIKE',
					'IN',
					'==',
					'!=',
					'>=',
					'<=',
					'=',
					'<',
					'>',
				]),
			]);
		// Základní výraz col = :param
		$singleexpr = new Sequence('expr', [
			$not->setOptional(),
			new Pattern('column', ['~[a-z0-9]+~']),
			$sep,
			$op,
			$sep,
			new Pattern('param (:name, :arg1, etc)', ['~\:[a-z0-9]+~']),
		]);
		// Ozávorkováním zanoříme řadu výrazů. Přičemž sám jsem výrazem.
		$subexpr = new Sequence('subexpr', [
			$not->setOptional(),
			new Pattern(Null, ['~\s*\(\s*~']),
			new Ref('chainexprs'), // Odkazujeme na combinator, který teprve nadefinujeme
			new Pattern(Null, ['~\s*\)~']),
		]);
		$expr = new OneOf(Null, [
			$singleexpr, // výraz, ...
			$subexpr, // ... ozávorkovaný výraz
		]);
		// Rada výrazů oddělených spojkou AND/OR
		$parser = new Variants('chainexprs', [
			$expr,
			$bool,
		], [$expr], [$expr]);

		list($token, $expected) = $parser->scan($src, 0, []);
		$this->assertSame($src, (string) $token);
		$this->assertSame([], $expected);
	}



	function dataBoolExpressionTree()
	{
		return [
			['col1 != :param1'],
			['NOT col1 != :param1'],
			['not col1 != :param1'],
			['col1 != :param1 OR col2 = :param2'],
			["col1 != :par1 AND col2 = :par2"],
			["col1 != :par1 AND col2 = :par2 OR col3 = :par3"],
			['(col1 != :param1 OR col2 = :param2)'],
			['((col1 != :param1 OR col2 = :param2))'],
			["col1 != :par1 AND NOT col2 = :par2"],
			['NOT (col1 != :param1 OR col2 = :param2)'],
			['NOT (col1 != :param1 OR NOT col2 = :param2)'],
			['NOT (col1 != :param1 OR not col2 = :param2)'],
			['NOT (col1 != :param1 OR (NOT col2 = :param2))'],
			['NOT (col1 != :param1 OR NOT (col2 = :param2))'],
			['NOT (col1 != :param1 OR  NOT (col2 = :param2))'],
			['NOT (col1 != :param1 OR  NOT  (col2 = :param2))'],
			['(col1 != :param1 OR col2 = :param2) AND NOT (col3 == :par1 AND col4 == :par2)'],
			['NOT (col1 != :param1 OR col2 = :param2) AND NOT (col3 == :par1 AND col4 == :par2)'],
			['NOT ( NOT ( NOT (col1 != :param1 OR col2 = :param2)))'],
			["col1 != :par1 or col2 = :par2"],
			["(col1 != :par1 or col2 = :par2)"],
			["col1 != :par1 AND col2 = :par2 OR col3 >= :par3 AND (col4 != :par4 AND col4 != :par5) AND NOT (col5 = :par5 AND col3 = :col5)"],
			['col1 IN :param1'],
			['col1 LIkE :param1'],
			['col1 NOT IN :param1'],
			['col1 NOT LIkE :param1'],
			['col1 NOT  LIkE :param1'],
		];
	}



	/*

shared-photos =
    {$userName} {$photoCount ->
        [one] added a new photo
       *[other] added {$photoCount} new photos
    } to {$userGender ->
        [male] his stream
        [female] her stream
       *[other] their stream
    }.

	 */
	function getFlowProject()
	{
		$skipIndent = [['{', '}']];

		$symbolPattern = '[a-z\-\$][a-zA-Z0-9\-]*';
		$valuePattern = '[a-z0-9][a-zA-Z0-9\-]*';

		$varname = new Pattern('varname', ['~\$[a-z0-9]+~']);
		$emptyline = new Pattern('emptyline', ['~^[\ \t]*$~']);
		$sep = new Whitechars(Null, False);
		$nl = new Pattern(Null, ['~\n+~'], False);
		$comment = new Pattern('comment', ['~^#.*$~m']);
		$identifier = new Pattern('identifier', ['~' . $symbolPattern . '~']);
		$textElement = new Pattern('text-element', ['~[^\{\}]+~s']);
		$variableReference = new Pattern('variable-reference', ['~\{' . $symbolPattern . '\}~i']);
		$option = new Sequence('select-option', [
			new Pattern('default?', ['~\s*\*?~']),
			new Pattern('option-identifier', ['~\[\s*' . $valuePattern . '\s*\]~']),
			new Pattern(Null, ['~[ \t]*~'], False),
			new Ref('pattern'),
		]);
		$selectOptions = new Variants('select-options', [
			$option,
			$nl,
		]);
		$selectExpression = new Sequence('select-expression', [
			new Pattern('select-start', ['~\{\s*~'], False),
			$identifier,
			$sep,
			new Match('assign', ['->'], False),
			//~ $sep,
			new Indent(Null, new Sequence(Null, [
				$nl,
				$selectOptions,
			]), $skipIndent),
			new Pattern('select-end', ['~\s*\}~'], False)
		]);
		$placeable = new Variants('placeable', [
			$nl,
			$variableReference,
			$selectExpression,
			$textElement,
		]);
		$message = new Sequence('message', [
			$identifier,
			$sep,
			new Match('assign', ['='], False),
			$sep,
			new Indent('pattern', $placeable, $skipIndent),
		]);
		$fluent = new Variants(Null, [
			$message,
			$comment,
			$nl,
		]);
		return $fluent;
	}



	function testFlowProject_1()
	{
		$src = 'shared-photos =
    {$userName} {$photoCount ->
        [one] added a new photo
        *[other] added {$photoCount} new photos
    } to {$userGender ->
        [male] his stream
        [female] her stream
        *[other] their stream
    }.

slovo = Nějaký text
	text pokračuje s odsazením
	a další řádka.
';
		$src = '
# comment
## comment 2
### comment 3

shared-photos = X
    {userName} {photoCount ->
        [one] added a new photo
        *[other] added {photoCount} new photos
    } to {userGender ->
        [male] his stream
        [female] her stream
        *[other] their stream
    }.
';

		list($token, $expected) = $this->getFlowProject()->scan($src, 0, []);
		$this->assertEquals([
			'# comment',
			'## comment 2',
			'### comment 3',
			[
				'shared-photos',
				[
					"X\n    ",
					'{userName}',
					' ',
					[
						'photoCount',
						[[
							['        ', '[one]', ['added a new photo']],
							['        *', '[other]', [
								'added ',
								'{photoCount}',
								" new photos",
							]],
						]],
					],
					' to ',
					[
						'userGender',
						[[
							[
								'        ',
								'[male]',
								['his stream'],
							],
							[
								'        ',
								'[female]',
								['her stream'],
							],
							[
								'        *',
								'[other]',
								['their stream'],
							],
						]],
					],
					'.',
				],
			],
		], self::unbox($token));

		$this->assertSame([], $expected);
	}



	function testNeonProject()
	{
		$src = 'une: 1
deux: Lorem ipsum doler ist
trois: [1, 2, 3]
quatre:
	- 1
	- 2
	- 3';

		$nl = new Pattern(Null, ['~\n+~'], False);
		$name = new Pattern('name', ['~[a-z]+~']);
		$text = new Pattern('text', ['~[^\n]+~']);
		$list = new Sequence('list', [
			new Pattern(Null, ['~\s*\[\s*~'], False),
			new Variants(Null, [
				new Numeric(Null),
				new Pattern(Null, ['~\s*,\s*~'], False),
			]),
			new Pattern(Null, ['~\s*\]~'], False),
		]);
		$list2 = new Sequence('list3', [
			$nl,
			new Variants(Null, [
				new Sequence(Null, [
					new Pattern(Null, ['~\s+-\s*~'], False),
					new Numeric(Null),
				]),
				$nl,
			]),
		]);
		$node = new Sequence('node', [
			$name,
			new Pattern(Null, ['~\:[ \t]*~'], False),
			new Indent(null, new OneOf(Null, [
				$list2,
				$list,
				$text,
			])),
		]);
		$neon = new Variants('neon', [
			$node,
			$nl,
		]);

		list($token, $expected) = $neon->scan($src, 0, []);
		$this->assertEquals([
			['une', '1'],
			['deux', 'Lorem ipsum doler ist'],
			['trois', ['1', '2', '3']],
			['quatre', ['1', '2', '3']],
		], self::unbox($token));
		$this->assertSame([], $expected);
	}



	function testFlatDevel()
	{
		$src = 'quatre:
	- 1
	- 2
	- 3
';

		$nl = new Pattern(Null, ['~\n+~'], False);
		$name = new Pattern('name', ['~[a-z]+~']);
		$text = new Pattern('text', ['~[^\n]+~']);
		$list2 = new Sequence('list2', [
			$nl,
			new Variants(Null, [
				new Sequence(Null, [
					new Pattern(Null, ['~\s+-\s*~'], False),
					new Numeric(Null),
				]),
				$nl,
			]),
		]);
		$node = new Sequence('node', [
			$name,
			new Pattern(Null, ['~\:[ \t]*~'], False),
			new Indent(null, new OneOf('content', [
				$list2,
				$text,
			])),
		]);
		$neon = new Variants('neon', [
			$node,
			$nl,
		]);

		list($token, $expected) = $neon->scan($src, 0, []);
		//~ dump($token->content[0]->content[1]/*->content[0]*/);
		$this->assertSame([
			['quatre', ['1','2','3']]
		], self::unbox($token));
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

}
