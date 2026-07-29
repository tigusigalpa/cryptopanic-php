<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use Throwable;

/**
 * Thrown when the CryptoPanic API responds with HTTP 429 (Too Many
 * Requests) and the configured number of automatic retries has been
 * exhausted. Carries the Retry-After header value for inspection.
 */
class RateLimitException extends ApiException
{
    public function __construct(
        string $message = 'CryptoPanic API rate limit exceeded.',
        array $responseBody = [],
        public readonly ?string $retryAfter = null,
        ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 429, $responseBody, $requestId, $previous);
    }
}
