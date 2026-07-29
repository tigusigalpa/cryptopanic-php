<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

use RuntimeException;

/**
 * Base exception for all CryptoPanic SDK errors.
 *
 * All other SDK-specific exceptions inherit from this class, providing a
 * common catch point for SDK errors. Catch this exception to handle any
 * error originating from the SDK.
 */
class CryptoPanicException extends RuntimeException
{
}
