<?php

if ($ready || $waiting) {
    accept();
}

// There is no boolean wrapping point in this indivisible condition.
if ($request->routeIs('licence-agreement.create.with.a.deliberately.long.indivisible.route.name.that.must.not.be.split.into.pieces')) {
    accept();
}

// Existing multiline layouts and line-comment placement stay untouched.
if (
    $request->routeIs('licence-agreement.create') // Leave this comment here.
    || $request->routeIs('licence-agreement.store')
    || $request->routeIs('download-licence-agreement')
) {
    accept();
}

// Keyword operators, for headers, and non-condition expressions are not supported.
if ($request->routeIs('licence-agreement.create') or $request->routeIs('licence-agreement.store') or $request->routeIs('download-licence-agreement')) {
    accept();
}

for (; $request->routeIs('licence-agreement.create') || $request->routeIs('licence-agreement.store') || $request->routeIs('download-licence-agreement');) {
    break;
}

$accepted = $request->routeIs('licence-agreement.create') || $request->routeIs('licence-agreement.store') || $request->routeIs('download-licence-agreement');
