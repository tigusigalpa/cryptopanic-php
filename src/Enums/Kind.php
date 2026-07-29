<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Enums;

/**
 * Known post kind values.
 */
enum Kind: string
{
    case News = 'news';
    case Media = 'media';
    case All = 'all';
}
