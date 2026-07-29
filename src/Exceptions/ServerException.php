<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use Throwable;

/**
 * Thrown when the CryptoPanic API responds with HTTP 500
 * (Internal Server Error).
 */
class ServerException extends ApiException
{
    public function __construct(
        string $message = 'CryptoPanic server error.',
        array $responseBody = [],
        ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 500, $responseBody, $requestId, $previous);
    }
}
