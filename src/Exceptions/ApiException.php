<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use Throwable;

/**
 * Base exception for errors returned by the CryptoPanic API.
 *
 * Carries the HTTP status code, decoded response body, and optional
 * request ID. The auth token is never included in the exception data.
 */
class ApiException extends CryptoPanicException
{
    /**
     * @param string               $message      Human-readable error message.
     * @param int                  $statusCode   HTTP status code.
     * @param array<string, mixed> $responseBody Decoded JSON response body, if available.
     * @param string|null          $requestId    Value of the X-Request-ID header, if present.
     * @param Throwable|null       $previous     Previous exception for chaining.
     */
    public function __construct(
        string $message,
        public readonly int $statusCode = 0,
        public readonly array $responseBody = [],
        public readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, $statusCode, $previous);
    }

    /**
     * Get the HTTP status code associated with this exception.
     */
    public function getStatusCode(): int
    {
        return $this->statusCode;
    }
}
