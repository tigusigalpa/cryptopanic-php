<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

use DateTimeImmutable;

/**
 * Represents a single news post in the CryptoPanic API response.
 * Plan-gated or potentially omitted fields are nullable.
 */
final class Post
{
    /**
     * @param PostInstrument[] $instruments
     */
    public function __construct(
        public readonly ?int $id = null,
        public readonly ?string $slug = null,
        public readonly ?string $title = null,
        public readonly ?string $description = null,
        public readonly ?DateTimeImmutable $publishedAt = null,
        public readonly ?DateTimeImmutable $createdAt = null,
        public readonly ?string $kind = null,
        public readonly ?PostSource $source = null,
        public readonly ?string $originalUrl = null,
        public readonly ?string $url = null,
        public readonly ?string $image = null,
        public readonly array $instruments = [],
        public readonly ?PostVotes $votes = null,
        public readonly ?int $panicScore = null,
        public readonly ?int $panicScore1h = null,
        public readonly ?PostAuthor $author = null,
        public readonly ?PostContent $content = null,
    ) {
    }
}
