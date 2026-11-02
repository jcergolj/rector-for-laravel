<?php

function fillProperties($object): void
{
    if ($object) {
        $first = $object?->section
            ?->first;
        // The second value.
        $second = $object?->section
            ?->second;
    }

    $fill = function () use ($object): void {
        $first = $object->section
            ->first;
        $second = $object->section
            ->second;
    };
}

$first = $object->section
    ->first;
$second = $object->section
    ->second;
-----
<?php

function fillProperties($object): void
{
    if ($object) {
        $first = $object?->section
            ?->first;

        // The second value.
        $second = $object?->section
            ?->second;
    }

    $fill = function () use ($object): void {
        $first = $object->section
            ->first;

        $second = $object->section
            ->second;
    };
}

$first = $object->section
    ->first;

$second = $object->section
    ->second;
