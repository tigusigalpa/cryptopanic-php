<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

/**
 * Represents a raw RSS feed response. The body is preserved as
 * raw text/XML rather than being coerced into the JSON post
 * page type.
 */
final class RSSResponse
{
    public function __construct(
        public readonly string $body = '',
    ) {
    }
}
