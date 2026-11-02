<?php

if ($request->matches(['first' => 'one', 'second' => 'two'])->allowed() && $request->routeIs('download-licence-agreement')) {
    accept();
}

if ($request->matches(check(['first' => 'one', 'second' => 'two'], 'context'))->allowed() || $request->routeIs('download-licence-agreement')) {
    accept();
}
-----
<?php

if (
    $request->matches([
        'first' => 'one',
        'second' => 'two',
    ])
        ->allowed() &&
    $request->routeIs('download-licence-agreement')
) {
    accept();
}

if (
    $request->matches(check(
        [
            'first' => 'one',
            'second' => 'two',
        ],
        'context',
    ))
        ->allowed() ||
    $request->routeIs('download-licence-agreement')
) {
    accept();
}
