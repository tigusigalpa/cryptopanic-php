<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Models;

/**
 * Represents the content object nested in a post.
 * This field is enterprise-gated and may be null or absent.
 */
final class PostContent
{
    public function __construct(
        public readonly ?string $raw = null,
    ) {
    }
}
