<?php

declare(strict_types=1);

/**
 * Basic example: fetch the latest CryptoPanic news posts.
 *
 * Usage:
 *   CRYPTOPANIC_AUTH_TOKEN=your-token php examples/basic.php
 */

require_once __DIR__ . '/../vendor/autoload.php';

use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\PostsQuery;

$token = getenv('CRYPTOPANIC_AUTH_TOKEN');
if (!$token) {
    fwrite(STDERR, "CRYPTOPANIC_AUTH_TOKEN is not set.\n");
    exit(1);
}

$client = CryptoPanicClient::fromEnv();

$page = $client->posts(new PostsQuery());

printf("Retrieved %d posts\n", count($page->results));
foreach ($page->results as $post) {
    printf("  [%s] %s\n", $post->kind ?? 'unknown', $post->title ?? 'untitled');
}
