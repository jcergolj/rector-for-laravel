<?php

class BooleanConditions
{
    public function check($request): void
    {
        if ($request->routeIs('licence-agreement.create') && ($request->isMethod('POST') || $request->routeIs('licence-agreement.store'))) {
            accept();
        } elseif (($request->routeIs('download-licence-agreement') || $request->routeIs('licence-agreement.store')) && $request->user()) {
            accept();
        }

        while ($request->routeIs('licence-agreement.create') /* before */ && /* after */ $request->routeIs('licence-agreement.store')) {
            break;
        }

        do {
            retry();
        } while (($request->routeIs('download-licence-agreement') || $request->routeIs('licence-agreement.store')) && $request->user());
    }
}
-----
<?php

class BooleanConditions
{
    public function check($request): void
    {
        if (
            $request->routeIs('licence-agreement.create') &&
            ($request->isMethod('POST') ||
            $request->routeIs('licence-agreement.store'))
        ) {
            accept();
        } elseif (
            ($request->routeIs('download-licence-agreement') ||
            $request->routeIs('licence-agreement.store')) &&
            $request->user()
        ) {
            accept();
        }

        while (
            $request->routeIs('licence-agreement.create') /* before */ &&
            /* after */ $request->routeIs('licence-agreement.store')
        ) {
            break;
        }

        do {
            retry();
        } while (
            ($request->routeIs('download-licence-agreement') ||
            $request->routeIs('licence-agreement.store')) &&
            $request->user()
        );
    }
}
