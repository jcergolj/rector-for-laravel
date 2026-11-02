<?php

function message($value, $other)
{
    return prefix('a sufficiently long prefix argument to make this expression exceed the soft line target').($value /* keep before dot */ . /* keep after dot */ $other).suffix();
}

$nested = render('a long contextual argument that makes this entire line longer than the configured soft target', prefix().suffix());
$arithmetic = prefix('a sufficiently long prefix argument to make this expression exceed the soft line target').(1 + 2).($value ?? 'fallback');
-----
<?php

function message($value, $other)
{
    return prefix('a sufficiently long prefix argument to make this expression exceed the soft line target').
        ($value /* keep before dot */.
        /* keep after dot */ $other).
        suffix();
}

$nested = render('a long contextual argument that makes this entire line longer than the configured soft target', prefix().
    suffix());
$arithmetic = prefix('a sufficiently long prefix argument to make this expression exceed the soft line target').
    (1 + 2).
    ($value ?? 'fallback');
