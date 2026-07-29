<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests;

use Orchestra\Testbench\TestCase as BaseTestCase;
use Tigusigalpa\CryptoPanic\Laravel\CryptoPanicServiceProvider;

/**
 * Base test case for Laravel-integrated (Feature) tests.
 * Uses Orchestra Testbench to bootstrap a minimal Laravel application.
 */
class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [CryptoPanicServiceProvider::class];
    }

    protected function getEnvironmentSetUp($app): void
    {
        $app['config']->set('cryptopanic.auth_token', 'test-secret-token');
        $app['config']->set('cryptopanic.api_plan', 'growth');
        $app['config']->set('cryptopanic.base_url', 'https://cryptopanic.com/api');
    }
}
