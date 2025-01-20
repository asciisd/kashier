<?php

namespace Asciisd\Kashier\Providers;

use Asciisd\Kashier\KashierService;
use Illuminate\Support\ServiceProvider;

class KashierServiceProvider extends ServiceProvider
{
    public function register(): void
    {
        $this->app->singleton('kashier', function () {
            return new KashierService();
        });
    }

    public function boot(): void
    {
        // Publish the configuration file
        $this->publishes([
            __DIR__.'/../../config/kashier.php' => config_path('kashier.php'),
        ], 'config');
    }
}
