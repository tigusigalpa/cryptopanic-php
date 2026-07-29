<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Enums;

/**
 * Known panic period values. Enterprise-gated in the reference.
 */
enum PanicPeriod: string
{
    case OneHour = '1h';
    case SixHours = '6h';
    case TwentyFourHours = '24h';
}
