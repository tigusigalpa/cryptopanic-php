<?php

declare(strict_types=1);

/**
 * Laravel usage example.
 *
 * This file demonstrates how to use the CryptoPanic SDK within a
 * Laravel application. It is not runnable standalone — it shows the
 * typical usage patterns.
 */

use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Laravel\Facades\CryptoPanic;
use Tigusigalpa\CryptoPanic\PostsQuery;

// 1. Using the facade
$page = CryptoPanic::posts(new PostsQuery(
    currencies: ['BTC'],
    filter: Filter::Rising,
    page: 1,
));

foreach ($page->results as $post) {
    echo $post->title . "\n";
}

// 2. Using dependency injection
app()->bind('example', function ($app) {
    $client = $app->make(\Tigusigalpa\CryptoPanic\CryptoPanicClient::class);
    $portfolio = $client->portfolio();
    echo json_encode($portfolio->raw, JSON_PRETTY_PRINT) . "\n";
});

// 3. RSS
$rss = CryptoPanic::postsRss(new PostsQuery(currencies: ['BTC']));
echo strlen($rss->body) . " bytes of RSS XML\n";

$newsRss = CryptoPanic::newsRss();
echo strlen($newsRss->body) . " bytes of news RSS XML\n";
