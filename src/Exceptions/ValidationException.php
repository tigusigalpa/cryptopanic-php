<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use Throwable;

/**
 * Thrown when local query validation fails before any API request
 * is made.
 */
class ValidationException extends CryptoPanicException
{
    public function __construct(string $message = 'Invalid query parameters.', ?Throwable $previous = null)
    {
        parent::__construct($message, 0, $previous);
    }
}
