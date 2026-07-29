<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Transport;

/**
 * Immutable HTTP response value object.
 */
final class HttpResponse
{
    /**
     * @param int                  $statusCode HTTP status code.
     * @param string               $body       Raw response body.
     * @param array<string,string> $headers    Response headers (lowercase keys).
     */
    public function __construct(
        public readonly int $statusCode,
        public readonly string $body,
        public readonly array $headers = [],
    ) {
    }

    /**
     * Get a specific response header by name (case-insensitive).
     */
    public function getHeader(string $name): ?string
    {
        return $this->headers[strtolower($name)] ?? null;
    }
}
