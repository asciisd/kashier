<?php

namespace Asciisd\Kashier\Tests;

use Asciisd\Kashier\Providers\KashierServiceProvider;
use Orchestra\Testbench\TestCase as BaseTestCase;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            KashierServiceProvider::class,
        ];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('kashier.mid', env('KASHIER_MERCHANT_ID'));
        $app['config']->set('kashier.apikey', env('KASHIER_API_KEY'));
        $app['config']->set('kashier.mode', env('KASHIER_MODE'));
    }
}
