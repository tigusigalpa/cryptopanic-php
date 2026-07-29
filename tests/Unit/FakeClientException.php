<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use Psr\Http\Client\ClientExceptionInterface;
use RuntimeException;

/**
 * Minimal PSR-18 ClientExceptionInterface implementation for testing.
 */
final class FakeClientException extends RuntimeException implements ClientExceptionInterface
{
}
