<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

/**
 * Represents the author of a post.
 */
final class PostAuthor
{
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $name = null,
        public readonly ?string $slug = null,
        public readonly ?string $url = null,
        public readonly ?string $avatar = null,
        public readonly ?string $twitter = null,
        public readonly ?string $facebook = null,
        public readonly ?string $linkedIn = null,
    ) {
    }
}
