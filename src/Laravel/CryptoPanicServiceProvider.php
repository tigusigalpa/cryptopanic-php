<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Laravel;

use Illuminate\Contracts\Foundation\Application;
use Illuminate\Support\ServiceProvider;
use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\CryptoPanicConfig;

/**
 * Laravel service provider for the CryptoPanic SDK.
 *
 * Registers the CryptoPanicClient as a singleton in the service
 * container, merges the package configuration, and publishes the
 * configuration file. No network requests are made at boot time.
 */
final class CryptoPanicServiceProvider extends ServiceProvider
{
    /**
     * Register services in the container.
     */
    public function register(): void
    {
        $this->mergeConfigFrom(
            __DIR__ . '/../../config/cryptopanic.php',
            'cryptopanic'
        );

        $this->app->singleton(CryptoPanicClient::class, function (Application $app): CryptoPanicClient {
            $config = $app->make('config')->get('cryptopanic');

            return new CryptoPanicClient(CryptoPanicConfig::fromArray([
                'auth_token' => $config['auth_token'] ?? '',
                'api_plan' => $config['api_plan'] ?? CryptoPanicConfig::DEFAULT_PLAN,
                'base_url' => $config['base_url'] ?? CryptoPanicConfig::DEFAULT_BASE_URL,
                'timeout' => $config['timeout'] ?? CryptoPanicConfig::DEFAULT_TIMEOUT,
                'retry_attempts' => $config['retry_attempts'] ?? CryptoPanicConfig::DEFAULT_RETRY_ATTEMPTS,
                'retry_delay' => $config['retry_delay'] ?? CryptoPanicConfig::DEFAULT_RETRY_DELAY,
                'retry_max_delay' => $config['retry_max_delay'] ?? CryptoPanicConfig::DEFAULT_RETRY_MAX_DELAY,
                'user_agent' => $config['user_agent'] ?? CryptoPanicConfig::DEFAULT_USER_AGENT,
            ]));
        });

        $this->app->alias(CryptoPanicClient::class, 'cryptopanic');
    }

    /**
     * Bootstrap services. Publishes the configuration file. No network
     * requests are performed here.
     */
    public function boot(): void
    {
        if ($this->app->runningInConsole()) {
            $this->publishes([
                __DIR__ . '/../../config/cryptopanic.php' => config_path('cryptopanic.php'),
            ], 'cryptopanic-config');
        }
    }
}
