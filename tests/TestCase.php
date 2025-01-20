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
        $app['config']->set('kashier.merchant_id', 'test-merchant-id');
        $app['config']->set('kashier.api_key', 'test-api-key');
        $app['config']->set('kashier.mode', 'test');
    }
}
