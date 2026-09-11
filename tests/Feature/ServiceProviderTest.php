<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Feature;

use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\Laravel\CryptoPanicServiceProvider;
use Tigusigalpa\CryptoPanic\Laravel\Facades\CryptoPanic;
use Tigusigalpa\CryptoPanic\Tests\TestCase;

class ServiceProviderTest extends TestCase
{
    public function test_client_is_registered_in_container(): void
    {
        $client = $this->app->make(CryptoPanicClient::class);
        self::assertInstanceOf(CryptoPanicClient::class, $client);
    }

    public function test_client_is_singleton(): void
    {
        $client1 = $this->app->make(CryptoPanicClient::class);
        $client2 = $this->app->make(CryptoPanicClient::class);
        self::assertSame($client1, $client2);
    }

    public function test_facade_resolves_client(): void
    {
        $client = CryptoPanic::getFacadeRoot();
        self::assertInstanceOf(CryptoPanicClient::class, $client);
    }

    public function test_config_is_published(): void
    {
        $config = $this->app['config']->get('cryptopanic');
        self::assertIsArray($config);
        self::assertArrayHasKey('auth_token', $config);
        self::assertArrayHasKey('api_plan', $config);
        self::assertArrayHasKey('base_url', $config);
        self::assertArrayHasKey('retry_max_delay', $config);
    }

    public function test_config_values_from_test_environment(): void
    {
        $config = $this->app['config']->get('cryptopanic');
        self::assertSame('test-secret-token', $config['auth_token']);
        self::assertSame('growth', $config['api_plan']);
    }

    public function test_service_provider_is_loaded(): void
    {
        $providers = $this->app->getProviders(CryptoPanicServiceProvider::class);
        self::assertCount(1, $providers);
    }
}
