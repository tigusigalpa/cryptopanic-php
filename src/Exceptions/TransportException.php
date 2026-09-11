<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use Throwable;

/**
 * Thrown when an HTTP transport error occurs (e.g. cURL failure,
 * timeout, or network issue).
 */
class TransportException extends CryptoPanicException
{
    public function __construct(
        string $message = '',
        int $code = 0,
        ?Throwable $previous = null,
        public readonly bool $retryable = true,
    ) {
        parent::__construct($message, $code, $previous);
    }
}
