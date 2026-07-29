<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Fake PSR-18 client that throws a transport-level exception for the
 * first N calls, then delegates to a real response for subsequent
 * calls. Used to test that CryptoPanicClient retries on transport
 * failures when using a PSR-18 transport.
 */
final class FakeThrowingPsr18Client implements ClientInterface
{
    private int $callCount = 0;

    public function __construct(
        private readonly int $failuresBeforeSuccess,
        private readonly ResponseInterface $successResponse,
    ) {
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        $this->callCount++;
        if ($this->callCount <= $this->failuresBeforeSuccess) {
            throw new FakeClientException('Simulated transport failure');
        }
        return $this->successResponse;
    }

    public function getCallCount(): int
    {
        return $this->callCount;
    }
}
