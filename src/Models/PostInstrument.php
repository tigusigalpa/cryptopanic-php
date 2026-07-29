<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

/**
 * Represents a currency instrument tagged on a post.
 */
final class PostInstrument
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $code = null,
        public readonly ?string $slug = null,
        public readonly ?string $title = null,
        public readonly ?float $volume = null,
        public readonly ?float $change = null,
        public readonly ?string $currency = null,
    ) {
    }
}
