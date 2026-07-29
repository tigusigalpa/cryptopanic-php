<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use Throwable;

/**
 * Thrown when the CryptoPanic API responds with HTTP 401
 * (Unauthorized), indicating an invalid or missing auth token.
 */
class UnauthorizedException extends ApiException
{
    public function __construct(
        string $message = 'Unauthorized — check your auth token.',
        array $responseBody = [],
        ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 401, $responseBody, $requestId, $previous);
    }
}
