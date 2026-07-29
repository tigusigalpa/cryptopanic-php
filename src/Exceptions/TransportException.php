<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

/**
 * Thrown when an HTTP transport error occurs (e.g. cURL failure,
 * timeout, or network issue).
 */
class TransportException extends CryptoPanicException
{
}
