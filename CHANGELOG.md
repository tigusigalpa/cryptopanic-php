# Changelog

All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](https://keepachangelog.com/en/1.1.0/),
and this project adheres to [Semantic Versioning](https://semver.org/spec/v2.0.0.html).

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
