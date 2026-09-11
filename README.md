# cryptopanic-php

![CryptoPanic PHP Laravel SDK](https://i.postimg.cc/nVggY4fL/cryptopanic-php-github.jpg)

[![Packagist](https://img.shields.io/packagist/v/tigusigalpa/cryptopanic-php.svg)](https://packagist.org/packages/tigusigalpa/cryptopanic-php)
[![License: MIT](https://img.shields.io/badge/License-MIT-yellow.svg)](https://opensource.org/licenses/MIT)
[![PHP](https://img.shields.io/badge/PHP-%3E%3D8.1-8892BF.svg)](https://www.php.net/)
[![Tests](https://github.com/tigusigalpa/cryptopanic-php/actions/workflows/tests.yml/badge.svg?branch=main)](https://github.com/tigusigalpa/cryptopanic-php/actions/workflows/tests.yml)
[![Coverage workflow](https://github.com/tigusigalpa/cryptopanic-php/actions/workflows/coverage.yml/badge.svg?branch=main)](https://github.com/tigusigalpa/cryptopanic-php/actions/workflows/coverage.yml)
[![CodeQL](https://github.com/tigusigalpa/cryptopanic-php/actions/workflows/codeql.yml/badge.svg?branch=main)](https://github.com/tigusigalpa/cryptopanic-php/actions/workflows/codeql.yml)
[![codecov](https://codecov.io/gh/tigusigalpa/cryptopanic-php/graph/badge.svg)](https://codecov.io/gh/tigusigalpa/cryptopanic-php)
[![PHPStan](https://img.shields.io/badge/PHPStan-level%205-brightgreen.svg)](https://phpstan.org/)
[![Latest release](https://img.shields.io/github/v/release/tigusigalpa/cryptopanic-php)](https://github.com/tigusigalpa/cryptopanic-php/releases)

A framework-neutral PHP SDK for the [CryptoPanic API](https://cryptopanic.com/developers/api/) with a first-class
Laravel bridge.

It gives your application typed post models, a small immutable query object, raw RSS access, safe token handling,
configurable retries, and exceptions that tell you what went wrong. Use it in a plain PHP worker, a Symfony-style
service, a Laravel controller, or anywhere Composer is available.

The SDK stays deliberately close to the public API. Known response shapes become models; RSS remains XML; and the
portfolio response remains raw because the public reference does not define a complete stable schema.

## Contents

- [Why this SDK](#why-this-sdk)
- [Requirements and installation](#requirements-and-installation)
- [Quick start](#quick-start)
- [Configuration](#configuration)
- [Working with posts](#working-with-posts)
- [Other endpoints](#other-endpoints)
- [Pagination](#pagination)
- [Errors](#errors)
- [Retries and rate limits](#retries-and-rate-limits)
- [Laravel](#laravel)
- [PSR-18 transport](#psr-18-transport)
- [Testing and development](#testing-and-development)

## Why this SDK?

Integrating a news API should not mean copying authentication, URL construction, JSON decoding, and retry logic into
every project. `cryptopanic-php` takes care of that plumbing and leaves you with a client that fits the way you already
build PHP applications:

- **Typed response models** — work with `Post`, `PostsPage`, `PostSource`, `PostVotes`, and related objects instead of
  anonymous arrays where the API shape is known.
- **A small, readable API** — create a client, build a `PostsQuery`, and call `posts()`.
- **Safe credentials** — the auth token is never included in exception data and is redacted from pagination URLs.
- **Flexible HTTP** — use the built-in cURL transport or inject a PSR-18 client and request factory.
- **Clear failures** — catch one base `CryptoPanicException` or a precise exception such as `UnauthorizedException` or
  `RateLimitException`.
- **Laravel-friendly, not Laravel-dependent** — auto-discovery, a facade, configuration publishing, and dependency
  injection are available when Laravel is present.
- **Strict and immutable** — the package uses strict typing and readonly configuration/query values.

## Requirements and installation

- PHP 8.1 or newer.
- `ext-json` and `ext-curl`.
- Composer.

The Laravel bridge is tested against Laravel 10 through 13. Laravel 13 itself requires PHP 8.3 or newer.

```bash
composer require tigusigalpa/cryptopanic-php
```

PSR-18 interfaces are part of the package contract. You do not need a PSR-18 implementation when using the default cURL
transport. If your application already uses a PSR-18 client, inject it as described
in [PSR-18 transport](#psr-18-transport).

## Quick start

### Framework-neutral PHP

Keep the token outside source control, for example in your process environment:

```bash
export CRYPTOPANIC_AUTH_TOKEN="your-token"
```

Then create the client and ask for a page of posts:

```php
<?php

declare(strict_types=1);

use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\PostsQuery;
use Tigusigalpa\CryptoPanic\Enums\Filter;

$client = CryptoPanicClient::fromEnv();

$page = $client->posts(new PostsQuery(
    currencies: ['BTC', 'ETH'],
    filter: Filter::Rising,
    page: 1,
));

foreach ($page->results as $post) {
    printf("[%s] %s\n", $post->kind ?? 'unknown', $post->title ?? 'Untitled');
}
```

If configuration already comes from your application container, construct it explicitly:

```php
use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\CryptoPanicConfig;

$client = new CryptoPanicClient(new CryptoPanicConfig(
    authToken: $token,
    apiPlan: 'growth',
    timeout: 10.0,
));
```

`CryptoPanicClient::fromEnv()` throws `ConfigurationException` when the token is missing. It is usually best to create
one client and reuse it rather than constructing a new client for every request.

## Configuration

### Environment variables

`CryptoPanicConfig::fromEnv()` reads the following values:

| Variable                     | Description                                              | Default                       |
|------------------------------|----------------------------------------------------------|-------------------------------|
| `CRYPTOPANIC_AUTH_TOKEN`     | CryptoPanic auth token; required                         | —                             |
| `CRYPTOPANIC_API_PLAN`       | Plan segment: `developer`, `growth`, or `enterprise`     | `growth`                      |
| `CRYPTOPANIC_BASE_URL`       | API base URL without the plan segment                    | `https://cryptopanic.com/api` |
| `CRYPTOPANIC_TIMEOUT`        | Request timeout in seconds                               | `15`                          |
| `CRYPTOPANIC_RETRY_ATTEMPTS` | Retries after the initial request for transient failures | `0`                           |
| `CRYPTOPANIC_RETRY_DELAY`    | Initial retry delay in seconds                           | `1`                           |
| `CRYPTOPANIC_RETRY_MAX_DELAY`| Maximum delay between retries in seconds                 | `30`                          |

The User-Agent has a safe package default. Set a custom value through `CryptoPanicConfig` or an array passed to
`fromArray()`; it is not read from an environment variable by `fromEnv()`.

Never commit a token, print it in logs, or paste a full authenticated URL into an issue. The SDK keeps the token out of
exception messages/data and redacts it from pagination URLs.

### Explicit configuration

`CryptoPanicConfig` is immutable. Its constructor uses camelCase PHP property names, while `fromArray()` accepts the
snake_case keys commonly used in environment/config files:

```php
$config = CryptoPanicConfig::fromArray([
    'auth_token' => $token,
    'api_plan' => 'growth',
    'timeout' => 10.0,
    'retry_attempts' => 3,
    'retry_delay' => 0.5,
    'retry_max_delay' => 10.0,
    'user_agent' => 'my-news-service/1.0',
]);

$client = new CryptoPanicClient($config);
```

## Working with posts

`PostsQuery` is an immutable value object. It keeps authentication out of query construction and validates only rules
that are safe to check locally. Plan permissions and evolving server-side values remain the responsibility of
CryptoPanic.

```php
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Enums\Kind;
use Tigusigalpa\CryptoPanic\PostsQuery;

$query = new PostsQuery(
    currencies: ['BTC', 'ETH', 'SOL'],
    regions: 'en',
    filter: Filter::Hot,
    kind: Kind::News,
    size: 20,
    page: 1,
);

$page = $client->posts($query);

foreach ($page->results as $post) {
    echo $post->title . PHP_EOL;

    if ($post->source !== null) {
        echo 'Source: ' . ($post->source->title ?? 'unknown') . PHP_EOL;
    }

    if ($post->publishedAt !== null) {
        echo 'Published: ' . $post->publishedAt->format(DATE_ATOM) . PHP_EOL;
    }
}
```

### Query fields

| Field         | Type                        | What it does                                                                                                             | Plan note        |
|---------------|-----------------------------|--------------------------------------------------------------------------------------------------------------------------|------------------|
| `public`      | `?bool`                     | Requests public mode when set.                                                                                           | All listed plans |
| `currencies`  | `list<string>`              | Sends a comma-separated list such as `['BTC', 'ETH']`.                                                                   | All listed plans |
| `regions`     | `string`                    | Sets a region code; the API default is `en`.                                                                             | All listed plans |
| `filter`      | `Filter\|string\|null`      | Known values include `rising`, `hot`, `bullish`, `bearish`, `important`, `saved`, and `lol`; custom strings are allowed. | All listed plans |
| `kind`        | `Kind\|string\|null`        | `news`, `media`, or `all`.                                                                                               | All listed plans |
| `following`   | `?bool`                     | Private-only following mode.                                                                                             | All listed plans |
| `lastPull`    | `?DateTimeImmutable`        | Sends an RFC 3339 / ISO-8601 timestamp.                                                                                  | Enterprise-gated |
| `panicPeriod` | `PanicPeriod\|string\|null` | `1h`, `6h`, or `24h`.                                                                                                    | Enterprise-gated |
| `panicSort`   | `PanicSort\|string\|null`   | `asc` or `desc`; requires `panicPeriod` locally.                                                                         | Enterprise-gated |
| `size`        | `?int`                      | Page size from 1 through 50.                                                                                             | Enterprise-gated |
| `page`        | `?int`                      | Page number from 1 through 50; the API default is 1.                                                                     | All listed plans |
| `withContent` | `?bool`                     | Requests full post content.                                                                                              | Enterprise-gated |
| `search`      | `string`                    | Searches for a non-empty string.                                                                                         | Enterprise-gated |

The query object omits empty values from the URL. Local validation rejects an invalid `size`/`page`, a blank search
string, or `panicSort` without `panicPeriod`.

### Immutable updates

Use `with()` when a worker or controller needs a related query without changing the original:

```php
$firstPage = new PostsQuery(currencies: ['BTC'], page: 1);
$secondPage = $firstPage->with(['page' => 2]);

// $firstPage is unchanged.
```

### Enums

Known values are available as backed enums, while the query still accepts custom strings for forward compatibility:

```php
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Enums\Kind;
use Tigusigalpa\CryptoPanic\Enums\PanicPeriod;
use Tigusigalpa\CryptoPanic\Enums\PanicSort;
use Tigusigalpa\CryptoPanic\Enums\Plan;

Filter::Rising;
Kind::News;
PanicPeriod::OneHour;
PanicSort::Desc;
Plan::Growth;
```

## Other endpoints

### Portfolio

`portfolio()` returns a `PortfolioResponse` whose `raw` property contains the decoded JSON value. The public API
reference does not define a complete portfolio schema, so the SDK preserves the response instead of guessing at domain
fields.

```php
$portfolio = $client->portfolio();

var_dump($portfolio->raw);
```

Treat `raw` as an integration boundary: inspect or map it into your own application DTO when you know the shape returned
for your account.

### RSS

`postsRss()` and `newsRss()` return an `RSSResponse` with the raw XML in `body`:

```php
$feed = $client->newsRss();
header('Content-Type: application/rss+xml');
echo $feed->body;
```

`postsRss()` accepts the same `PostsQuery` filters and adds `format=rss`. RSS is not converted into `PostsPage`; the
public reference documents the RSS response as 20 items regardless of plan.

## Supported endpoints

The CryptoPanic public API documentation uses one shared API Reference page, so the links below intentionally point to
the same page.

| Endpoint                 | PHP method                  | Return value        |
|--------------------------|-----------------------------|---------------------|
| `GET /posts/`            | `$client->posts($query)`    | `PostsPage`         |
| `GET /portfolio/`        | `$client->portfolio()`      | `PortfolioResponse` |
| `GET /posts/?format=rss` | `$client->postsRss($query)` | `RSSResponse`       |
| `GET /news/rss/`         | `$client->newsRss()`        | `RSSResponse`       |

See the [CryptoPanic API reference](https://cryptopanic.com/developers/api/)
and [plans page](https://cryptopanic.com/developers/api/plans) for server-side behavior.

## Response models

### `PostsPage`

| Property                    | Meaning                                                             |
|-----------------------------|---------------------------------------------------------------------|
| `results`                   | Array of `Post` objects for the current page.                       |
| `next` / `previous`         | Pagination URLs with `auth_token` redacted; empty when unavailable. |
| `hasNext` / `hasPrevious`   | Whether another page exists.                                        |
| `nextPage` / `previousPage` | Page numbers extracted from the pagination URLs, when present.      |

### `Post`

A `Post` can contain its ID, slug, title, description, publication and creation dates, kind, source, original URL,
CryptoPanic URL, image, instruments, votes, author, and optional content. API fields that may be omitted or plan-gated
are nullable:

```php
foreach ($page->results as $post) {
    if ($post->title === null) {
        continue;
    }

    echo $post->title . PHP_EOL;

    if ($post->content !== null) {
        echo $post->content->raw . PHP_EOL;
    }
}
```

Dates are exposed as `DateTimeImmutable` instances when the server returns a parseable value. Unknown additive fields
from the API are ignored while known fields are mapped into models.

## Pagination

CryptoPanic returns `next` and `previous` URLs in the response envelope. The SDK redacts the `auth_token` query
parameter before exposing them and extracts the page number when possible.

The client does not take a pagination URL as input. Use `nextPage` to create a new immutable query, preserving any
filters you originally selected:

```php
$query = new PostsQuery(
    currencies: ['BTC', 'ETH'],
    filter: Filter::Rising,
    page: 1,
);

while (true) {
    $page = $client->posts($query);

    foreach ($page->results as $post) {
        echo $post->title . PHP_EOL;
    }

    if (!$page->hasNext || $page->nextPage === null) {
        break;
    }

    $query = $query->with(['page' => $page->nextPage]);
}
```

## Errors

All SDK-specific exceptions inherit from `CryptoPanicException`, so you can choose the level of detail your application
needs:

- Catch a specific exception when the response needs a specific action.
- Catch `ApiException` when you need the HTTP status, response body, or request ID.
- Catch `CryptoPanicException` as a final SDK-level fallback.

```php
use Tigusigalpa\CryptoPanic\Exceptions\ApiException;
use Tigusigalpa\CryptoPanic\Exceptions\CryptoPanicException;
use Tigusigalpa\CryptoPanic\Exceptions\ForbiddenException;
use Tigusigalpa\CryptoPanic\Exceptions\RateLimitException;
use Tigusigalpa\CryptoPanic\Exceptions\UnauthorizedException;

try {
    $page = $client->posts(new PostsQuery(currencies: ['BTC']));
} catch (UnauthorizedException $e) {
    // 401: refresh or replace the token.
} catch (ForbiddenException $e) {
    // 403: check account access and plan permissions.
} catch (RateLimitException $e) {
    // 429: slow down; retryAfter may contain server guidance.
    $retryAfter = $e->retryAfter;
} catch (ApiException $e) {
    // Other non-2xx API responses.
    error_log(sprintf('CryptoPanic HTTP %d: %s', $e->statusCode, $e->getMessage()));
} catch (CryptoPanicException $e) {
    // Configuration, validation, transport, or decoding failure.
}
```

### Exception hierarchy

```text
CryptoPanicException
├── ConfigurationException    — Missing or invalid configuration
├── ValidationException       — Local query validation failed
├── TransportException        — cURL or PSR-18 transport failure
├── DecodingException         — Invalid JSON response
└── ApiException              — Non-2xx API response
    ├── UnauthorizedException — HTTP 401
    ├── ForbiddenException    — HTTP 403
    ├── RateLimitException    — HTTP 429, with retryAfter when supplied
    └── ServerException       — HTTP 500
```

`ApiException` exposes `statusCode`, `responseBody`, and `requestId` when available. The auth token is not included in
those values. HTTP 502 and 503 are retried when configured, then surface as the general `ApiException` if the final
response is still unsuccessful.

## Retries and rate limits

Retries are **disabled by default**. Enable them in the immutable configuration:

```php
$config = new CryptoPanicConfig(
    authToken: $token,
    retryAttempts: 3,
    retryDelay: 0.5,
);

$client = new CryptoPanicClient($config);
```

`retryAttempts` counts retries after the initial request. A value of `3` allows up to four requests in total.

- **Retried HTTP statuses:** 429, 500, 502, and 503.
- **Retried transport failures:** cURL and PSR-18 transport exceptions are retried when attempts remain.
- **Not retried:** 400, 401, 403, 404, and other non-transient API errors.
- **Delay:** capped exponential backoff using `retryDelay * 2^attempt`; `retryMaxDelay` defaults to 30 seconds.
- **Server guidance:** `Retry-After` is used when supplied as seconds or an HTTP date, then capped by `retryMaxDelay`.

Retries are useful for temporary outages and rate limits; they cannot fix a missing token, an account plan restriction,
or an invalid query.

## Laravel

The Laravel bridge is auto-discovered when the package is installed in a Laravel application. It registers
`CryptoPanicClient` as a singleton, merges package configuration, and provides the `CryptoPanic` facade alias.

### Environment and published configuration

Add the token and settings to `.env`:

```dotenv
CRYPTOPANIC_AUTH_TOKEN=your-token
CRYPTOPANIC_API_PLAN=growth
CRYPTOPANIC_TIMEOUT=15
CRYPTOPANIC_RETRY_ATTEMPTS=2
CRYPTOPANIC_RETRY_DELAY=1
CRYPTOPANIC_RETRY_MAX_DELAY=30
```

Publish the package configuration when you want to inspect or customize it:

```bash
php artisan vendor:publish --tag=cryptopanic-config
```

### Facade

```php
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Laravel\Facades\CryptoPanic;
use Tigusigalpa\CryptoPanic\PostsQuery;

$page = CryptoPanic::posts(new PostsQuery(
    currencies: ['BTC'],
    filter: Filter::Important,
));

$feed = CryptoPanic::newsRss();
```

### Dependency injection

```php
use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\PostsQuery;

final class NewsController
{
    public function __construct(
        private readonly CryptoPanicClient $cryptopanic,
    ) {
    }

    public function index(): mixed
    {
        return $this->cryptopanic->posts(new PostsQuery(currencies: ['BTC']));
    }
}
```

The service provider performs no network request while Laravel boots.

## PSR-18 transport

The default transport uses cURL. If your application already standardizes on PSR-18, pass both a
`Psr\Http\Client\ClientInterface` implementation and a `Psr\Http\Message\RequestFactoryInterface`:

```bash
composer require guzzlehttp/guzzle
```

```php
use GuzzleHttp\Client;
use GuzzleHttp\Psr7\HttpFactory;
use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\CryptoPanicConfig;

$client = new CryptoPanicClient(
    config: CryptoPanicConfig::fromEnv(),
    psrClient: new Client(),
    requestFactory: new HttpFactory(),
);
```

If the PSR-18 client throws a `ClientExceptionInterface`, the SDK wraps it in `TransportException`, so the configured
retry policy still applies.

## Plan caveats

The free Developer API plan was discontinued. The SDK exposes the documented plan values but does not claim that a
particular account has access to them.

Parameters such as `lastPull`, `panicPeriod`, `size`, `withContent`, and `search` may be plan-gated. The SDK validates
obvious syntax locally, while CryptoPanic remains the authority for account entitlements and evolving server rules.
Check your [account dashboard](https://cryptopanic.com/account/) and
the [plans page](https://cryptopanic.com/developers/api/plans) for current commercial details.

## Testing and development

Run the quality checks from the package directory:

```bash
composer install
composer test
composer test:unit
composer test:feature
composer test:coverage
composer analyse
composer validate
```

The test suite uses HTTP fakes and does not require live API credentials. Retry timing can be replaced with
`setSleeper()` to keep tests fast and deterministic. The coverage workflow stores the Clover report as a GitHub Actions
artifact and uploads it to Codecov. For private repositories, add the `CODECOV_TOKEN` Actions secret after installing
the Codecov GitHub app. To run `composer test:coverage` locally, enable PCOV or Xdebug.

For local development:

```bash
git clone https://github.com/tigusigalpa/cryptopanic-php.git
cd cryptopanic-php
composer install
composer test
```

## Release, security, and contribution

This project follows [Semantic Versioning](https://semver.org/). See [CHANGELOG.md](CHANGELOG.md) for release
history, [CONTRIBUTING.md](CONTRIBUTING.md) for contribution guidelines, and [SECURITY.md](SECURITY.md) for
vulnerability reporting and credential-safety guidance.

## License

MIT — See [LICENSE](LICENSE) for details.

## Author

**Igor Sazonov**

- GitHub: [https://github.com/tigusigalpa](https://github.com/tigusigalpa)
- Email: sovletig@gmail.com
