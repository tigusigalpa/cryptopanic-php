<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Exceptions;

/**
 * Thrown when the CryptoPanic API configuration is invalid or incomplete,
 * such as a missing auth token.
 */
class ConfigurationException extends CryptoPanicException
{
}
