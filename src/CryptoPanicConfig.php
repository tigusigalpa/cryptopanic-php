<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic;

/**
 * Immutable configuration for the CryptoPanic PHP SDK.
 *
 * Holds all settings needed to communicate with the CryptoPanic API:
 * auth token, plan segment, base URL, timeout, retry behaviour, and
 * user agent. Instances are created via the constructor or the static
 * fromArray / fromEnv factories and cannot be mutated after
 * construction.
 */
final class CryptoPanicConfig
{
    public const DEFAULT_BASE_URL = 'https://cryptopanic.com/api';
    public const DEFAULT_PLAN = 'growth';
    public const DEFAULT_TIMEOUT = 15.0;
    public const DEFAULT_RETRY_ATTEMPTS = 0;
    public const DEFAULT_RETRY_DELAY = 1.0;
    public const DEFAULT_RETRY_MAX_DELAY = 30.0;
    public const DEFAULT_USER_AGENT = 'cryptopanic-php/1.1.0';

    /**
     * @param string $authToken     CryptoPanic auth token.
     * @param string $apiPlan       API plan segment: developer, growth, enterprise.
     * @param string $baseUrl       Base URL for API requests (without plan segment).
     * @param float  $timeout       Request timeout in seconds.
     * @param int    $retryAttempts Number of retries on 429/5xx (0 = no retry).
     * @param float  $retryDelay    Base delay in seconds for exponential backoff.
     * @param float  $retryMaxDelay Maximum delay in seconds between retries.
     * @param string $userAgent     User-Agent header value.
     */
    public function __construct(
        public readonly string $authToken = '',
        public readonly string $apiPlan = self::DEFAULT_PLAN,
        public readonly string $baseUrl = self::DEFAULT_BASE_URL,
        public readonly float $timeout = self::DEFAULT_TIMEOUT,
        public readonly int $retryAttempts = self::DEFAULT_RETRY_ATTEMPTS,
        public readonly float $retryDelay = self::DEFAULT_RETRY_DELAY,
        public readonly float $retryMaxDelay = self::DEFAULT_RETRY_MAX_DELAY,
        public readonly string $userAgent = self::DEFAULT_USER_AGENT,
    ) {
    }

    /**
     * Create a configuration from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(
            authToken: (string)($data['auth_token'] ?? ''),
            apiPlan: (string)($data['api_plan'] ?? self::DEFAULT_PLAN),
            baseUrl: (string)($data['base_url'] ?? self::DEFAULT_BASE_URL),
            timeout: (float)($data['timeout'] ?? self::DEFAULT_TIMEOUT),
            retryAttempts: (int)($data['retry_attempts'] ?? self::DEFAULT_RETRY_ATTEMPTS),
            retryDelay: (float)($data['retry_delay'] ?? self::DEFAULT_RETRY_DELAY),
            retryMaxDelay: (float)($data['retry_max_delay'] ?? self::DEFAULT_RETRY_MAX_DELAY),
            userAgent: (string)($data['user_agent'] ?? self::DEFAULT_USER_AGENT),
        );
    }

    /**
     * Create a configuration from environment variables.
     */
    public static function fromEnv(): self
    {
        return new self(
            authToken: self::env('CRYPTOPANIC_AUTH_TOKEN', ''),
            apiPlan: self::env('CRYPTOPANIC_API_PLAN', self::DEFAULT_PLAN),
            baseUrl: self::env('CRYPTOPANIC_BASE_URL', self::DEFAULT_BASE_URL),
            timeout: (float)self::env('CRYPTOPANIC_TIMEOUT', (string)self::DEFAULT_TIMEOUT),
            retryAttempts: (int)self::env('CRYPTOPANIC_RETRY_ATTEMPTS', (string)self::DEFAULT_RETRY_ATTEMPTS),
            retryDelay: (float)self::env('CRYPTOPANIC_RETRY_DELAY', (string)self::DEFAULT_RETRY_DELAY),
            retryMaxDelay: (float)self::env('CRYPTOPANIC_RETRY_MAX_DELAY', (string)self::DEFAULT_RETRY_MAX_DELAY),
        );
    }

    private static function env(string $name, string $default): string
    {
        $value = getenv($name);
        return $value === false || $value === '' ? $default : $value;
    }
}
