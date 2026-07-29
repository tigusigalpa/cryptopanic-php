<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | CryptoPanic Auth Token
    |--------------------------------------------------------------------------
    |
    | Your CryptoPanic auth token, sent as the auth_token query parameter
    | on every request. Get one from your CryptoPanic account dashboard.
    |
    | NEVER commit your real token to source control. Use environment
    | variables or a secure secret manager.
    |
    */
    'auth_token' => env('CRYPTOPANIC_AUTH_TOKEN', ''),

    /*
    |--------------------------------------------------------------------------
    | API Plan
    |--------------------------------------------------------------------------
    |
    | The plan segment used in the base URL: developer, growth, or
    | enterprise. Check the plans page for commercial details:
    | https://cryptopanic.com/developers/api/plans
    |
    */
    'api_plan' => env('CRYPTOPANIC_API_PLAN', 'growth'),

    /*
    |--------------------------------------------------------------------------
    | Base URL
    |--------------------------------------------------------------------------
    |
    | The base URL for the CryptoPanic API. The SDK appends /{plan}/v2/
    | automatically.
    |
    */
    'base_url' => env('CRYPTOPANIC_BASE_URL', 'https://cryptopanic.com/api'),

    /*
    |--------------------------------------------------------------------------
    | Timeout
    |--------------------------------------------------------------------------
    |
    | Request timeout in seconds.
    |
    */
    'timeout' => env('CRYPTOPANIC_TIMEOUT', 15.0),

    /*
    |--------------------------------------------------------------------------
    | Retry Attempts & Delay
    |--------------------------------------------------------------------------
    |
    | Number of automatic retries on HTTP 429 and selected 5xx responses
    | (0 = no retry), and the base delay in seconds for exponential
    | backoff between retries.
    |
    */
    'retry_attempts' => env('CRYPTOPANIC_RETRY_ATTEMPTS', 0),

    'retry_delay' => env('CRYPTOPANIC_RETRY_DELAY', 1.0),

    /*
    |--------------------------------------------------------------------------
    | User-Agent
    |--------------------------------------------------------------------------
    |
    | The User-Agent header value sent with every request.
    |
    */
    'user_agent' => env('CRYPTOPANIC_USER_AGENT', 'cryptopanic-php/1.0.0'),
];
