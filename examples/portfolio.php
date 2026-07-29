<?php

declare(strict_types=1);

/**
 * Portfolio example: fetch the user's portfolio.
 *
 * Usage:
 *   CRYPTOPANIC_AUTH_TOKEN=your-token php examples/portfolio.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\CryptoPanic\CryptoPanicClient;

$token = getenv('CRYPTOPANIC_AUTH_TOKEN');
if (!$token) {
    fwrite(STDERR, "CRYPTOPANIC_AUTH_TOKEN is not set.\n");
    exit(1);
}

$client = CryptoPanicClient::fromEnv();

$portfolio = $client->portfolio();

echo "Portfolio:\n";
echo json_encode($portfolio->raw, JSON_PRETTY_PRINT) . "\n";
