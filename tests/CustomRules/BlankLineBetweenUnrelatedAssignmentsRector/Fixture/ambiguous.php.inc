<?php

function ambiguous($object, $property, $variable, $input): void
{
    $first = 1;
    $$variable = 2;
    $second = 3;
    $this->{$property} = 4;
    $third = 5;
    $items['key'] = 6;
    $fourth = 7;
    [$a, $b] = $input;
    $fifth = 8;
    $reference =& $fifth;
    $sixth = 9;
    $chained = $other = 10;
    $seventh = 11;
    $dynamic = $$variable;
    $eighth = 12;
    $dynamicProperty = $this->{$property};
    $ninth = 13;
    $incremented = $ninth++;
    $tenth = 14;
    $hidden = compact('tenth');
    $eleventh = 15;
    $object->value = 16;
    $twelfth = 17;
    $thirteenth += 18;
    $fourteenth = 19;
}
