<?php

namespace Jcergolj\RectorForLaravel;

use Illuminate\Support\ServiceProvider;

class RectorForLaravelServiceProvider extends ServiceProvider
{
    public function boot(): void
    {
        $this->publishes([
            __DIR__ . '/../config/rector.php' => base_path('rector.php'),
        ], 'rector-for-laravel-config');
    }

    public function register(): void
    {
        //
    }
}
