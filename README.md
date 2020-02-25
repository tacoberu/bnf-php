A PHP BNF like parser
=====================

This is a PHP implementation of BNF like parser.


Installation
------------

The recommended way to install is via Composer:

        composer require tacoberu/bnf



Usage
-----

```php

require __dir__ . '/vendor/autoload.php';

use Taco\BNF\Parser;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Whitechars;

$parser = new Parser([
	new Whitechars(Null, False),
	new Pattern('element', ['~[^\n]+~']),
]);
$tree = $parser->parse('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
');

print_r($tree); /*

array (
    [0] => Taco\BNF\Token (
		[type] => Taco\BNF\Combinators\Pattern (...)
		[content] => "-brand-name = Foo 3000"
		[start] => 1
		[end] => 23
	)
    [0] => Taco\BNF\Token (
		[type] => Taco\BNF\Combinators\Pattern (...)
		[content] => "welcome = Welcome, {$name}, to {-brand-name}!"
		[start] => 24
		[end] => 69
	)
)

*/
```

or more complex:

```php

require __dir__ . '/vendor/autoload.php';

use Taco\BNF\Parser;
use Taco\BNF\Combinators\Pattern;
use Taco\BNF\Combinators\Whitechars;

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
$tree = $parser->parse('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
');

print_r($tree); /*

array (
    [0] => Taco\BNF\Token (
		[type] => Taco\BNF\Combinators\Sequence (...)
		[content] => array(
			[0] => Taco\BNF\Token (
				[type] => Taco\BNF\Combinators\Pattern (...)
				[content] => "-brand-name"
				[start] => 1
				[end] => 12
			)
			[1] => Taco\BNF\Token (
				[type] => Taco\BNF\Combinators\Pattern (...)
				[content] => "Foo 3000"
				[start] => 15
				[end] => 23
			)
		)
		[start] => 1
		[end] => 23
	)
    [0] => Taco\BNF\Token (
		[type] => Taco\BNF\Combinators\Pattern (...)
		[content] => array(
			[0] => Taco\BNF\Token (
				[type] => Taco\BNF\Combinators\Pattern (...)
				[content] => "welcome"
				[start] => 24
				[end] => 31
			)
			[1] => Taco\BNF\Token (
				[type] => Taco\BNF\Combinators\Pattern (...)
				[content] => "Welcome, {$name}, to {-brand-name}!"
				[start] => 34
				[end] => 69
			)
		)
		[start] => 24
		[end] => 69
	)
)

*/
```

See more examples in 'tests/ExhibitionTest.php'.
