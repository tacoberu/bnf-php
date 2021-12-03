<?php
/**
 * Copyright (c) since 2004 Martin Takáč
 * @author Martin Takáč <martin@takac.name>
 */

namespace Taco\BNF;

use PHPUnit\Framework\TestCase;
use LogicException;
use Taco\BNF\Combinators\Whitechars;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Match;
use Taco\BNF\Combinators\Sequence;
use Taco\BNF\Combinators\Variants;
use Taco\BNF\Combinators\OneOf;
use Taco\BNF\Combinators\Text;
use Taco\BNF\Combinators\Until;


class ExhibitionTest extends TestCase
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
		$expr = new Sequence('expr (col = :param)', [
			$not->setOptional(),
			new Pattern('column', ['~[a-z][a-zA-Z0-9]*~']),
			$sep,
			$op,
			$sep,
			new Pattern('param (:name, :arg1, etc)', ['~\:[a-z][a-z0-9]*~']),
		]);
		// We enclose a number of expressions with parentheses. And I am an expression myself.
		$subexpr = new Sequence('subexpr', [
			$not->setOptional(),
			new Pattern(Null, ['~\s*\(\s*~']),
			new Ref('chain-of-expressions'), // We refer to the combinator, which we have just defined.
			new Pattern(Null, ['~\s*\)~']),
		]);
		$expr = new OneOf(Null, [
			$expr,
			$subexpr,
		]);
		// A series of expressions separated by an AND / OR conjunction.
		$parser = new Variants('chain-of-expressions', [
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



	function testTextParse()
	{
		$parser = new Text('foo');
		list($token, $expected) = $parser->scan('Lorem "This is inner\"s text" ipsum doler ist.', 6, []);
		$this->assertEquals('"This is inner"s text"', $token);
		$this->assertEquals(6, $token->start);
		$this->assertEquals(29, $token->end);
		$this->assertEquals([], $expected);
	}



	function testFluentProject1()
	{
		$src = '
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
';
		$parser = new Parser([
			new Whitechars(Null, False),
			new Pattern('element', ['~[^\n]+~']),
		]);

		$tree = $parser->parse($src);
		$this->assertCount(2, $tree->content);
		$this->assertEquals('-brand-name = Foo 3000', (string)$tree->content[0]);
		$this->assertEquals('welcome = Welcome, {$name}, to {-brand-name}!', (string)$tree->content[1]);
	}



	function testFluentProject2()
	{
		$src = '
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
';
		$parser = new Parser([
			new Whitechars(Null, False),
			new Sequence('element', [
				new Pattern('id', ['~[a-z\-]+~']),
				new Whitechars(Null, False),
				new Match(Null, ['='], False),
				new Whitechars(Null, False),
				new Pattern('element', ['~[^\n]+~']),
			]),
		]);

		$tree = $parser->parse($src);
		$this->assertCount(2, $tree->content);
		$this->assertCount(2, $tree->content[0]->content);
		$this->assertEquals('-brand-name', $tree->content[0]->content[0]->content);
		$this->assertEquals('-brand-name   Foo 3000', (string)$tree->content[0]);
		$this->assertEquals('welcome   Welcome, {$name}, to {-brand-name}!', (string)$tree->content[1]);
	}



	function testDocCommentAnnotation1()
	{
		$input = '
			@author John Dee
			@package Project
		';

		$parser = new Variants(Null, [
			new Pattern(Null, ['~\n+~'], False),
			new Pattern(Null, ['~[\t ]+~'], False),
			new Sequence('anotation', [
				(new Whitechars(Null, False))->setOptional(),
				new Match(Null, ['@'], False),
				new Pattern('name', ['~[a-z][a-zA-Z0-9\-]*~']),
				new Pattern(Null, ['~[\t ]+~'], False),
				new Pattern('value', ['~[^\n]+~s']),
			]),
		]);

		list($token, $expected) = $parser->scan($input, 0, []);
		$this->assertEquals([], $expected);
		$this->assertCount(2, $token->content);
		$this->assertEquals("author John Dee", (string)$token->content[0]);
		$this->assertEquals("package Project", (string)$token->content[1]);
		$this->assertEquals("author", (string)$token->content[0]->content[0]);
		$this->assertEquals("John Dee", (string)$token->content[0]->content[1]);
	}



	function testDocCommentAnnotation2()
	{
		$input = '
			@author John Dee
			@package Project
			@email name@domain.tld
		';

		$parser = new Variants(Null, [
			new Pattern(Null, ['~\n+~'], False),
			new Sequence('anotation', [
				(new Whitechars(Null, False))->setOptional(),
				new Match(Null, ['@'], False),
				new Pattern('name', ['~[a-z][a-zA-Z0-9\-]*~']),
				new Whitechars(Null, False),
				new Until('value', ['~\n[\t\ ]*\@[a-z][a-zA-Z0-9\-]*~']),
			]),
		]);

		list($token, $expected) = $parser->scan($input, 0, []);
		$this->assertEquals([], $expected);
		$this->assertEquals(0, $token->start);
		$this->assertEquals(69, $token->end);
		$this->assertCount(3, $token->content);
		$this->assertEquals("author John Dee", (string)$token->content[0]);
		$this->assertEquals("package Project", (string)$token->content[1]);
		$this->assertEquals("email name@domain.tld\n\t\t", (string)$token->content[2]);
		$this->assertEquals("author", (string)$token->content[0]->content[0]);
		$this->assertEquals("John Dee", (string)$token->content[0]->content[1]);
	}



	function testDocCommentAnnotation3()
	{
		$input = '
			@email name@domain.tld
			sdf sdf lsk l skdfjlks d
			@author John Dee
		';

		$parser = new Sequence('anotation', [
				(new Whitechars(Null, False))->setOptional(),
				new Match(Null, ['@'], False),
				new Pattern('name', ['~[a-z][a-zA-Z0-9\-]*~']),
				new Whitechars(Null, False),
				new Until('value', ['~\n[\t\ ]*\@[a-z][a-zA-Z0-9\-]*~']),
			]);
		list($token, $expected) = $parser->scan($input, 0, []);
		$this->assertEquals([], $expected);
		$this->assertEquals(0, $token->start);
		$this->assertEquals(54, $token->end);
		$this->assertCount(2, $token->content);
		$this->assertEquals("email", (string)$token->content[0]);
		$this->assertEquals("name@domain.tld\n\t\t\tsdf sdf lsk l skdfjlks d", (string)$token->content[1]);
	}

}
