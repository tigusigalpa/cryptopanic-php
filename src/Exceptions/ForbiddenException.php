<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use Throwable;

/**
 * Thrown when the CryptoPanic API responds with HTTP 403
 * (Forbidden), indicating an access or plan restriction.
 */
class ForbiddenException extends ApiException
{
    public function __construct(
        string $message = 'Forbidden — access or plan restriction.',
        array $responseBody = [],
        ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct($message, 403, $responseBody, $requestId, $previous);
    }
}
