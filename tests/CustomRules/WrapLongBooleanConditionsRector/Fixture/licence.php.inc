<?php

if ($request->routeIs('licence-agreement.create') || $request->routeIs('licence-agreement.store') || $request->routeIs('download-licence-agreement')) {
    // Existing body unchanged.
    accept();
}
-----
<?php

if (
    $request->routeIs('licence-agreement.create') ||
    $request->routeIs('licence-agreement.store') ||
    $request->routeIs('download-licence-agreement')
) {
    // Existing body unchanged.
    accept();
}
