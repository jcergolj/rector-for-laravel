<?php

$short = ['one', 'two'];
$empty = [];
$long = ['https://example.com/this/is/an/indivisible/string/that/must/not/be/split/or/rewritten/just/to/enforce/a/soft/line/length/target'];
$alreadyMultiline = [
    'first' => 'https://example.com/this/is/an/indivisible/string/that/must/not/be/split/or/rewritten/just/to/enforce/a/soft/line/length/target', 'second' => true,
];
$callable = example(...);
$string = "A string that contains ['not', 'an', 'array'] and is deliberately long enough to exceed the soft target without a sensible wrapping point.";
