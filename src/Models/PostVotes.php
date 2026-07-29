<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

/**
 * Represents the vote counts on a post.
 */
final class PostVotes
{
    public function __construct(
        public readonly ?int $positive = null,
        public readonly ?int $negative = null,
        public readonly ?int $important = null,
        public readonly ?int $liked = null,
        public readonly ?int $disliked = null,
        public readonly ?int $lol = null,
        public readonly ?int $toxic = null,
        public readonly ?int $comments = null,
        public readonly ?int $saved = null,
    ) {
    }
}
