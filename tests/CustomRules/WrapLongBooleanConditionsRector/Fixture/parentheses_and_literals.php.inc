<?php

if (((/* opening */ $request->routeIs('licence-agreement.create') || $request->routeIs('licence-agreement.store') /* closing */))) {
    if ($request->routeIs('download-licence-agreement') && $request->routeIs('licence-agreement.store') && $request->user()?->can('accept')) {
        accept();
    }
}

if ($request->matches('https://example.com/path?literal=||&&') || $request->matches('/this-is-a-deliberately-long-regex-(foo|bar)/')) {
    accept();
}
-----
<?php

if (
    ((/* opening */ $request->routeIs('licence-agreement.create') ||
    $request->routeIs('licence-agreement.store') /* closing */))
) {
    if (
        $request->routeIs('download-licence-agreement') &&
        $request->routeIs('licence-agreement.store') &&
        $request->user()?->can('accept')
    ) {
        accept();
    }
}

if (
    $request->matches('https://example.com/path?literal=||&&') ||
    $request->matches('/this-is-a-deliberately-long-regex-(foo|bar)/')
) {
    accept();
}
