# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

## [1.1.0] - 2026-09-11

### Added
- `retry_max_delay` / `CRYPTOPANIC_RETRY_MAX_DELAY` configuration, defaulting to 30 seconds, to cap retry waits.
- Support for both delta-seconds and HTTP-date forms of the `Retry-After` header.
- Early validation for malformed base URLs, plans, timeouts, and retry configuration.
- Production-pipeline tests using a queue-backed PSR-18 client instead of a duplicated test client implementation.
- A CI matrix covering the PHP 8.1 framework-neutral client plus supported Laravel 12 and 13 integrations.
- GitHub Actions workflows for the PHP/Laravel test matrix, PCOV coverage with Codecov upload, and weekly CodeQL scanning of GitHub Actions workflows.
- Dependabot updates for Composer and GitHub Actions dependencies.

### Changed
- API error messages and decoded error data now redact the configured auth token, including URL-encoded forms.
- cURL requests no longer automatically follow redirects because the API token is sent in the query string.
- cURL honours sub-second configured timeouts.
- cURL retries only failures that are plausibly transient; permanent local failures return immediately.
- Retry backoff is safely capped and cannot overflow for a large attempt count.

## [1.0.0] - 2025-01-15

### Added
- Initial release of `tigusigalpa/cryptopanic-php`.
- Core `CryptoPanicClient` with framework-neutral design.
- `CryptoPanicConfig` immutable configuration with `fromArray` and `fromEnv` factories.
- `PostsQuery` immutable value object with `with()` builder and `validate()`.
- `posts(PostsQuery $query): PostsPage` method (GET /posts/).
- `portfolio(): PortfolioResponse` method (GET /portfolio/) with opaque raw JSON response.
- `postsRss(PostsQuery $query): RSSResponse` method (GET /posts/?format=rss).
- `newsRss(): RSSResponse` method (GET /news/rss/).
- Typed response models: `Post`, `PostSource`, `PostInstrument`, `PostVotes`, `PostContent`, `PostAuthor`, `PostsPage`, `PortfolioResponse`, `RSSResponse`.
- Enums: `Plan`, `Filter`, `Kind`, `PanicPeriod`, `PanicSort`.
- Exception hierarchy: `CryptoPanicException`, `ApiException`, `ConfigurationException`, `ValidationException`, `UnauthorizedException`, `ForbiddenException`, `RateLimitException`, `ServerException`, `TransportException`, `DecodingException`.
- Token redaction in pagination URLs and error messages.
- Bounded GET retry behavior with `Retry-After` header support and exponential backoff.
- Testable sleeper strategy for deterministic retry tests.
- cURL transport (default) and PSR-18 compatible transport support.
- Laravel bridge: `CryptoPanicServiceProvider`, `CryptoPanic` facade, publishable `config/cryptopanic.php`.
- Environment variable configuration: `CRYPTOPANIC_AUTH_TOKEN`, `CRYPTOPANIC_API_PLAN`, `CRYPTOPANIC_BASE_URL`, `CRYPTOPANIC_TIMEOUT`, `CRYPTOPANIC_RETRY_ATTEMPTS`, `CRYPTOPANIC_RETRY_DELAY`.
- PHPUnit test suite covering configuration, query encoding, pagination redaction, error mapping, retries, nullability, and token redaction.
- PHPStan static analysis configuration.
- GitHub Actions CI workflow.
- MIT LICENSE, CHANGELOG.md, CONTRIBUTING.md, SECURITY.md.
