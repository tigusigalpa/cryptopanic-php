<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Enums;

/**
 * Known panic sort directions. Enterprise-gated in the reference.
 * Rejected locally when panic_period is absent.
 */
enum PanicSort: string
{
    case Asc = 'asc';
    case Desc = 'desc';
}
