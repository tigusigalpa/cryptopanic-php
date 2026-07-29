<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

use DateTimeImmutable;

/**
 * Represents the source object nested in a Post.
 */
final class PostSource
{
    public function __construct(
        public readonly ?string $title = null,
        public readonly ?string $region = null,
        public readonly ?string $domain = null,
        public readonly ?string $path = null,
        public readonly ?DateTimeImmutable $createdAt = null,
    ) {
    }
}
