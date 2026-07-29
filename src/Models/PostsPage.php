<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

/**
 * Represents the paginated envelope returned by GET /posts/.
 * Pagination URLs are redacted: any auth_token query parameter
 * is removed before exposure.
 *
 * @property Post[] $results
 */
final class PostsPage
{
    /**
     * @param Post[] $results
     */
    public function __construct(
        public readonly array $results = [],
        public readonly string $next = '',
        public readonly string $previous = '',
        public readonly bool $hasNext = false,
        public readonly bool $hasPrevious = false,
        public readonly ?int $nextPage = null,
        public readonly ?int $previousPage = null,
    ) {
    }
}
