<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use LogicException;
use Psr\Http\Client\ClientInterface;
use Psr\Http\Message\RequestInterface;
use Psr\Http\Message\ResponseInterface;

/**
 * Queue-backed PSR-18 client used to exercise the production SDK request
 * pipeline without opening a network connection.
 */
final class FakeResponsePsr18Client implements ClientInterface
{
    /** @var list<ResponseInterface> */
    private array $responses;

    /** @var list<RequestInterface> */
    private array $requests = [];

    /**
     * @param list<ResponseInterface> $responses
     */
    public function __construct(array $responses)
    {
        $this->responses = $responses;
    }

    public function sendRequest(RequestInterface $request): ResponseInterface
    {
        if ($this->responses === []) {
            throw new LogicException('No fake PSR-18 response was configured.');
        }

        $this->requests[] = $request;
        $index = min(count($this->requests) - 1, count($this->responses) - 1);

        return $this->responses[$index];
    }

    public function getCallCount(): int
    {
        return count($this->requests);
    }

    public function getLastRequest(): ?RequestInterface
    {
        return $this->requests === [] ? null : $this->requests[array_key_last($this->requests)];
    }
}
