<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tigusigalpa\CryptoPanic\CryptoPanicConfig;

class CryptoPanicConfigTest extends TestCase
{
    public function test_defaults(): void
    {
        $config = new CryptoPanicConfig(authToken: 'test');
        self::assertSame('test', $config->authToken);
        self::assertSame('growth', $config->apiPlan);
        self::assertSame('https://cryptopanic.com/api', $config->baseUrl);
        self::assertSame(15.0, $config->timeout);
        self::assertSame(0, $config->retryAttempts);
        self::assertSame(1.0, $config->retryDelay);
        self::assertSame(30.0, $config->retryMaxDelay);
        self::assertSame('cryptopanic-php/1.1.0', $config->userAgent);
    }

    public function test_from_array(): void
    {
        $config = CryptoPanicConfig::fromArray([
            'auth_token' => 'my-token',
            'api_plan' => 'enterprise',
            'base_url' => 'https://custom.example.com',
            'timeout' => 30.0,
            'retry_attempts' => 5,
            'retry_delay' => 2.0,
            'retry_max_delay' => 10.0,
            'user_agent' => 'myapp/1.0',
        ]);

        self::assertSame('my-token', $config->authToken);
        self::assertSame('enterprise', $config->apiPlan);
        self::assertSame('https://custom.example.com', $config->baseUrl);
        self::assertSame(30.0, $config->timeout);
        self::assertSame(5, $config->retryAttempts);
        self::assertSame(2.0, $config->retryDelay);
        self::assertSame(10.0, $config->retryMaxDelay);
        self::assertSame('myapp/1.0', $config->userAgent);
    }

    public function test_from_env_preserves_zero_retry_delay(): void
    {
        putenv('CRYPTOPANIC_RETRY_DELAY=0');

        try {
            $config = CryptoPanicConfig::fromEnv();
            self::assertSame(0.0, $config->retryDelay);
        } finally {
            putenv('CRYPTOPANIC_RETRY_DELAY');
        }
    }

    public function test_from_array_defaults(): void
    {
        $config = CryptoPanicConfig::fromArray([]);

        self::assertSame('', $config->authToken);
        self::assertSame(CryptoPanicConfig::DEFAULT_PLAN, $config->apiPlan);
        self::assertSame(CryptoPanicConfig::DEFAULT_BASE_URL, $config->baseUrl);
    }
}
