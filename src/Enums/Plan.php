<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Enums;

/**
 * API plan segments used in the CryptoPanic base URL.
 *
 * Check the plans page for commercial details:
 * https://cryptopanic.com/developers/api/plans
 */
enum Plan: string
{
    case Developer = 'developer';
    case Growth = 'growth';
    case Enterprise = 'enterprise';
}
