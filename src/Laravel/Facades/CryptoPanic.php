<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Laravel\Facades;

use Illuminate\Support\Facades\Facade;
use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\Models\PortfolioResponse;
use Tigusigalpa\CryptoPanic\Models\PostsPage;
use Tigusigalpa\CryptoPanic\Models\RSSResponse;
use Tigusigalpa\CryptoPanic\PostsQuery;

/**
 * Laravel facade exposing the CryptoPanic API.
 *
 * @method static PostsPage         posts(PostsQuery $query)
 * @method static PortfolioResponse portfolio()
 * @method static RSSResponse       postsRss(PostsQuery $query)
 * @method static RSSResponse       newsRss()
 *
 * @see CryptoPanicClient
 */
final class CryptoPanic extends Facade
{
    /**
     * Get the registered name of the component in the service container.
     */
    protected static function getFacadeAccessor(): string
    {
        return CryptoPanicClient::class;
    }
}
