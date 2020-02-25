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

$parser = new Parser([
	'blank' => Parser::pattern('\s+', false),
	'element' => Parser::pattern('[^\n]+'),
]);
$tree = $parser->parse('
-brand-name = Foo 3000
welcome = Welcome, {$name}, to {-brand-name}!
');

print_r($tree); /*

Array
(
    [0] => stdClass Object
        (
            [name] => element
            [start] => 9
            [content] => Array
                (
                    [0] => stdClass Object
                        (
                            [content] => -brand-name
                            [start] => 9
                            [name] => id
                            [end] => 20
                        )

                    [1] => stdClass Object
                        (
                            [content] => Foo 3000
                            [start] => 23
                            [name] => msg
                            [end] => 31
                        )

                )

            [end] => 31
        )

    [1] => stdClass Object
        (
            [name] => element
            [start] => 34
            [content] => Array
                (
                    [0] => stdClass Object
                        (
                            [content] => welcome
                            [start] => 34
                            [name] => id
                            [end] => 41
                        )

                    [1] => stdClass Object
                        (
                            [content] => Welcome, {$name}, to {-brand-name}!
                            [start] => 44
                            [name] => msg
                            [end] => 79
                        )

                )

            [end] => 79
        )

)

*/
```


Status
------

Draft.
