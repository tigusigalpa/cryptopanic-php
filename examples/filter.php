<?php

declare(strict_types=1);

/**
 * Filter example: fetch rising BTC/ETH news posts.
 *
 * Usage:
 *   CRYPTOPANIC_AUTH_TOKEN=your-token php examples/filter.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Enums\Kind;
use Tigusigalpa\CryptoPanic\PostsQuery;

$token = getenv('CRYPTOPANIC_AUTH_TOKEN');
if (!$token) {
    fwrite(STDERR, "CRYPTOPANIC_AUTH_TOKEN is not set.\n");
    exit(1);
}

$client = CryptoPanicClient::fromEnv();

$page = $client->posts(new PostsQuery(
    currencies: ['BTC', 'ETH'],
    filter: Filter::Rising,
    kind: Kind::News,
));

printf("Rising BTC/ETH news (%d results):\n", count($page->results));
foreach ($page->results as $post) {
    printf("  - %s\n", $post->title ?? 'untitled');
}
