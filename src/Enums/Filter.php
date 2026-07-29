<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Enums;

/**
 * Known post filter values. Custom string values are allowed for
 * forward compatibility.
 */
enum Filter: string
{
    case Rising = 'rising';
    case Hot = 'hot';
    case Bullish = 'bullish';
    case Bearish = 'bearish';
    case Important = 'important';
    case Saved = 'saved';
    case Lol = 'lol';
}
