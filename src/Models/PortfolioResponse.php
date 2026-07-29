<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

/**
 * Opaque/generic wrapper for the GET /portfolio/ endpoint.
 *
 * The public API reference does not specify a complete response
 * schema, so the raw decoded JSON is retained instead of fabricating
 * a domain model. Future schema expansion is backward compatible
 * because callers access data through the Raw property.
 *
 * @property mixed $raw
 */
final class PortfolioResponse
{
    public function __construct(
        public readonly mixed $raw = null,
    ) {
    }
}
