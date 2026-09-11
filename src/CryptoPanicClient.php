<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic;

use DateTimeImmutable;
use Psr\Http\Client\ClientExceptionInterface;
use Psr\Http\Client\ClientInterface as Psr18Client;
use Psr\Http\Message\RequestFactoryInterface;
use Tigusigalpa\CryptoPanic\Exceptions\ApiException;
use Tigusigalpa\CryptoPanic\Exceptions\ConfigurationException;
use Tigusigalpa\CryptoPanic\Exceptions\DecodingException;
use Tigusigalpa\CryptoPanic\Exceptions\ForbiddenException;
use Tigusigalpa\CryptoPanic\Exceptions\RateLimitException;
use Tigusigalpa\CryptoPanic\Exceptions\ServerException;
use Tigusigalpa\CryptoPanic\Exceptions\TransportException;
use Tigusigalpa\CryptoPanic\Exceptions\UnauthorizedException;
use Tigusigalpa\CryptoPanic\Models\Post;
use Tigusigalpa\CryptoPanic\Models\PostAuthor;
use Tigusigalpa\CryptoPanic\Models\PostContent;
use Tigusigalpa\CryptoPanic\Models\PostInstrument;
use Tigusigalpa\CryptoPanic\Models\PostSource;
use Tigusigalpa\CryptoPanic\Models\PostVotes;
use Tigusigalpa\CryptoPanic\Models\PortfolioResponse;
use Tigusigalpa\CryptoPanic\Models\PostsPage;
use Tigusigalpa\CryptoPanic\Models\RSSResponse;
use Tigusigalpa\CryptoPanic\Transport\CurlTransport;
use Tigusigalpa\CryptoPanic\Transport\HttpResponse;

/**
 * Main CryptoPanic API client.
 *
 * Provides access to the CryptoPanic API endpoints: Posts, Portfolio,
 * and RSS feeds. Supports both an internal cURL transport (default)
 * and any PSR-18 compatible HTTP client.
 *
 * The auth token is sent as the auth_token query parameter on every
 * request, as required by the CryptoPanic API. It is never placed in
 * a URL path, user agent, exception message, or log record.
 */
class CryptoPanicClient
{
    private CurlTransport $curlTransport;
    private ?Psr18Client $psrClient = null;
    private ?RequestFactoryInterface $requestFactory = null;

    /**
     * @var callable|null
     */
    private mixed $sleeper = null;

    public function __construct(
        private readonly CryptoPanicConfig $config,
        ?Psr18Client $psrClient = null,
        ?RequestFactoryInterface $requestFactory = null,
    ) {
        $this->validateConfiguration();

        $this->curlTransport = new CurlTransport();
        $this->psrClient = $psrClient;
        $this->requestFactory = $requestFactory;
    }

    /**
     * Create a client from environment variables.
     */
    public static function fromEnv(): self
    {
        return new self(CryptoPanicConfig::fromEnv());
    }

    /**
     * Create a client from an associative array.
     *
     * @param array<string, mixed> $data
     */
    public static function fromArray(array $data): self
    {
        return new self(CryptoPanicConfig::fromArray($data));
    }

    /**
     * Set a custom sleeper callable for testable retry timing.
     *
     * @param callable|null $sleeper A callable that receives a float delay in seconds.
     */
    public function setSleeper(?callable $sleeper): void
    {
        $this->sleeper = $sleeper;
    }

    /**
     * Get the configuration object.
     */
    public function getConfig(): CryptoPanicConfig
    {
        return $this->config;
    }

    /**
     * Validate settings that would otherwise cause an opaque transport error
     * or an unbounded retry delay.
     *
     * @throws ConfigurationException
     */
    private function validateConfiguration(): void
    {
        if (trim($this->config->authToken) === '') {
            throw new ConfigurationException(
                'CryptoPanic auth token is required. Set it via CryptoPanicConfig or the CRYPTOPANIC_AUTH_TOKEN environment variable.'
            );
        }

        if (preg_match('/\A[A-Za-z0-9_-]+\z/D', $this->config->apiPlan) !== 1) {
            throw new ConfigurationException('CryptoPanic API plan must contain only letters, numbers, hyphens, or underscores.');
        }

        $baseUrl = parse_url($this->config->baseUrl);
        if (
            $baseUrl === false
            || !isset($baseUrl['scheme'], $baseUrl['host'])
            || !in_array(strtolower($baseUrl['scheme']), ['http', 'https'], true)
            || isset($baseUrl['query'], $baseUrl['fragment'], $baseUrl['user'], $baseUrl['pass'])
        ) {
            throw new ConfigurationException('CryptoPanic base URL must be an absolute HTTP(S) URL without credentials, query parameters, or a fragment.');
        }

        if (!is_finite($this->config->timeout) || $this->config->timeout <= 0) {
            throw new ConfigurationException('CryptoPanic timeout must be a positive finite number of seconds.');
        }

        if ($this->config->retryAttempts < 0) {
            throw new ConfigurationException('CryptoPanic retry attempts cannot be negative.');
        }

        if (!is_finite($this->config->retryDelay) || $this->config->retryDelay < 0) {
            throw new ConfigurationException('CryptoPanic retry delay must be a non-negative finite number of seconds.');
        }

        if (!is_finite($this->config->retryMaxDelay) || $this->config->retryMaxDelay <= 0) {
            throw new ConfigurationException('CryptoPanic retry maximum delay must be a positive finite number of seconds.');
        }
    }

    /**
     * Retrieve a paginated list of news posts (GET /posts/).
     *
     * Documentation: https://cryptopanic.com/developers/api/
     *
     * @throws ApiException
     * @throws TransportException
     * @throws ConfigurationException
     */
    public function posts(PostsQuery $query): PostsPage
    {
        $query->validate();
        $params = $query->toQueryArray();
        $decoded = $this->doGet('/posts/', $params, 0);

        return $this->parsePostsPage($decoded);
    }

    /**
     * Retrieve the user's portfolio (GET /portfolio/).
     *
     * Available under Growth and Enterprise plans. The response is
     * returned as an opaque PortfolioResponse with raw decoded JSON.
     *
     * Documentation: https://cryptopanic.com/developers/api/
     *
     * @throws ApiException
     * @throws TransportException
     */
    public function portfolio(): PortfolioResponse
    {
        $decoded = $this->doGet('/portfolio/', [], 0);
        return new PortfolioResponse(raw: $decoded);
    }

    /**
     * Retrieve posts with format=rss, returning raw RSS XML.
     * The reference says format=rss returns 20 items regardless of plan.
     *
     * Documentation: https://cryptopanic.com/developers/api/
     *
     * @throws ApiException
     * @throws TransportException
     */
    public function postsRss(PostsQuery $query): RSSResponse
    {
        $query->validate();
        $params = $query->toQueryArray();
        $params['format'] = 'rss';
        $body = $this->doGetRaw('/posts/', $params, 0);
        return new RSSResponse(body: $body);
    }

    /**
     * Retrieve the public CryptoPanic RSS news feed (GET /news/rss/).
     *
     * Documentation: https://cryptopanic.com/developers/api/
     *
     * @throws ApiException
     * @throws TransportException
     */
    public function newsRss(): RSSResponse
    {
        $body = $this->doGetRaw('/news/rss/', [], 0);
        return new RSSResponse(body: $body);
    }

    /**
     * Perform a GET request and return the decoded JSON response.
     *
     * @param string               $path  API path (e.g. "/posts/").
     * @param array<string, mixed> $query Query parameters.
     *
     * @return mixed Decoded JSON response.
     *
     * @throws ApiException
     * @throws TransportException
     */
    private function doGet(string $path, array $query, int $attempt): mixed
    {
        $url = $this->buildUrl($path, $query);
        $headers = $this->buildHeaders('application/json');

        try {
            $response = $this->sendRequest($url, $headers);
        } catch (TransportException $e) {
            if ($e->retryable && $attempt < $this->config->retryAttempts) {
                $this->sleep($this->calculateRetryDelay(null, $attempt));
                return $this->doGet($path, $query, $attempt + 1);
            }
            throw $e;
        }

        if ($this->shouldRetry($response->statusCode) && $attempt < $this->config->retryAttempts) {
            $delay = $this->calculateRetryDelay($response, $attempt);
            $this->sleep($delay);
            return $this->doGet($path, $query, $attempt + 1);
        }

        return $this->handleResponse($response, $attempt);
    }

    /**
     * Perform a GET request and return the raw response body as a string.
     * Used for RSS endpoints that return XML.
     *
     * @param string               $path  API path.
     * @param array<string, mixed> $query Query parameters.
     *
     * @throws ApiException
     * @throws TransportException
     */
    private function doGetRaw(string $path, array $query, int $attempt): string
    {
        $url = $this->buildUrl($path, $query);
        $headers = $this->buildHeaders('application/rss+xml, application/xml, text/xml, */*');

        try {
            $response = $this->sendRequest($url, $headers);
        } catch (TransportException $e) {
            if ($e->retryable && $attempt < $this->config->retryAttempts) {
                $this->sleep($this->calculateRetryDelay(null, $attempt));
                return $this->doGetRaw($path, $query, $attempt + 1);
            }
            throw $e;
        }

        if ($this->shouldRetry($response->statusCode) && $attempt < $this->config->retryAttempts) {
            $delay = $this->calculateRetryDelay($response, $attempt);
            $this->sleep($delay);
            return $this->doGetRaw($path, $query, $attempt + 1);
        }

        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            return $response->body;
        }

        $this->throwForStatus($response, $attempt);
    }

    /**
     * Build the full request URL with query parameters.
     * The auth_token is always appended as a query parameter.
     *
     * @param string               $path
     * @param array<string, mixed> $query
     */
    private function buildUrl(string $path, array $query): string
    {
        $url = rtrim($this->config->baseUrl, '/') . '/' . $this->config->apiPlan . '/v2' . $path;
        $query['auth_token'] = $this->config->authToken;

        $queryString = http_build_query($query);
        if ($queryString !== '') {
            $url .= '?' . $queryString;
        }

        return $url;
    }

    /**
     * Build HTTP headers for the request.
     *
     * @return array<string, string>
     */
    private function buildHeaders(string $accept): array
    {
        return [
            'Accept' => $accept,
            'User-Agent' => $this->config->userAgent,
        ];
    }

    /**
     * Send the HTTP request using either PSR-18 or the internal cURL transport.
     *
     * @param string               $url
     * @param array<string, string> $headers
     *
     * @throws TransportException
     */
    private function sendRequest(string $url, array $headers): HttpResponse
    {
        if ($this->psrClient !== null && $this->requestFactory !== null) {
            return $this->sendPsr18Request($url, $headers);
        }

        return $this->curlTransport->get($url, $headers, $this->config->timeout, $this->config->authToken);
    }

    /**
     * Send a request via the PSR-18 client.
     *
     * @param string               $url
     * @param array<string, string> $headers
     */
    private function sendPsr18Request(string $url, array $headers): HttpResponse
    {
        $request = $this->requestFactory->createRequest('GET', $url);

        foreach ($headers as $key => $value) {
            $request = $request->withHeader($key, $value);
        }

        try {
            $psrResponse = $this->psrClient->sendRequest($request);
        } catch (ClientExceptionInterface $e) {
            // Do not chain the original exception: PSR-18 implementations
            // sometimes include the full request URL in their error message.
            throw new TransportException('PSR-18 client error: ' . $this->redactString($e->getMessage()));
        }

        $responseHeaders = [];
        foreach ($psrResponse->getHeaders() as $key => $values) {
            $responseHeaders[strtolower($key)] = $values[0] ?? '';
        }

        return new HttpResponse(
            statusCode: $psrResponse->getStatusCode(),
            body: (string)$psrResponse->getBody(),
            headers: $responseHeaders,
        );
    }

    /**
     * Handle the HTTP response, mapping errors to exceptions.
     */
    private function handleResponse(HttpResponse $response, int $attempt): mixed
    {
        $body = $response->body;

        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            return $this->decodeBody($body);
        }

        $this->throwForStatus($response, $attempt);
    }

    /**
     * Throw the appropriate exception for a non-2xx response.
     *
     * @throws ApiException
     */
    private function throwForStatus(HttpResponse $response, int $attempt): never
    {
        $decoded = $this->redactToken($this->decodeBodySafe($response->body));
        $message = $this->extractMessage($decoded);
        $requestId = $response->getHeader('x-request-id');

        throw match ($response->statusCode) {
            401 => new UnauthorizedException($message, is_array($decoded) ? $decoded : [], $requestId),
            403 => new ForbiddenException($message, is_array($decoded) ? $decoded : [], $requestId),
            429 => new RateLimitException(
                $message,
                is_array($decoded) ? $decoded : [],
                $response->getHeader('retry-after'),
                $requestId,
            ),
            500 => new ServerException($message, is_array($decoded) ? $decoded : [], $requestId),
            default => new ApiException(
                $message,
                $response->statusCode,
                is_array($decoded) ? $decoded : [],
                $requestId,
            ),
        };
    }

    /**
     * Decode the response body as JSON.
     *
     * @throws DecodingException
     */
    private function decodeBody(string $body): mixed
    {
        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            throw new DecodingException('Failed to decode JSON response: ' . json_last_error_msg());
        }

        return $decoded;
    }

    /**
     * Decode the response body as JSON, returning the raw string if
     * decoding fails. Used for error responses where we want to
     * extract a message without throwing.
     */
    private function decodeBodySafe(string $body): mixed
    {
        if ($body === '') {
            return null;
        }

        $decoded = json_decode($body, true);
        if (json_last_error() !== JSON_ERROR_NONE) {
            return $body;
        }

        return $decoded;
    }

    /**
     * Extract a human-readable message from the decoded response.
     */
    private function extractMessage(mixed $decoded): string
    {
        if (is_array($decoded)) {
            return (string)($decoded['message'] ?? $decoded['msg'] ?? $decoded['error'] ?? 'Unknown error');
        }
        if (is_string($decoded)) {
            return strlen($decoded) > 200 ? substr($decoded, 0, 200) : $decoded;
        }
        return 'Unknown error';
    }

    /**
     * Determine if a status code should trigger a retry.
     */
    private function shouldRetry(int $statusCode): bool
    {
        return in_array($statusCode, [429, 500, 502, 503], true);
    }

    /**
     * Calculate the retry delay, honoring both forms of Retry-After and
     * capping all waits. Pass null for $response when retrying after a
     * transport-level failure with no HTTP response.
     */
    private function calculateRetryDelay(?HttpResponse $response, int $attempt): float
    {
        $maxDelay = $this->config->retryMaxDelay;
        $retryAfter = $this->parseRetryAfter($response?->getHeader('retry-after'));
        if ($retryAfter !== null) {
            return min($retryAfter, $maxDelay);
        }

        $delay = min($this->config->retryDelay, $maxDelay);
        for ($index = 0; $index < $attempt && $delay < $maxDelay; $index++) {
            $delay = min($delay * 2, $maxDelay);
        }

        return $delay;
    }

    /**
     * Parse Retry-After as either delta-seconds or an HTTP-date.
     */
    private function parseRetryAfter(?string $retryAfter): ?float
    {
        if ($retryAfter === null || trim($retryAfter) === '') {
            return null;
        }

        $value = trim($retryAfter);
        if (is_numeric($value) && (float)$value >= 0) {
            return (float)$value;
        }

        try {
            $delay = (new DateTimeImmutable($value))->getTimestamp() - time();
        } catch (\Throwable) {
            return null;
        }

        return (float)max(0, $delay);
    }

    /**
     * Sleep for the given delay, using the custom sleeper if set.
     */
    private function sleep(float $delay): void
    {
        if ($delay <= 0) {
            return;
        }

        if ($this->sleeper !== null) {
            ($this->sleeper)($delay);
        } else {
            usleep((int)($delay * 1_000_000));
        }
    }

    /**
     * Parse the decoded JSON into a PostsPage with typed Post models.
     *
     */
    private function parsePostsPage(mixed $decoded): PostsPage
    {
        if (!is_array($decoded)) {
            throw new DecodingException('Expected a JSON object for the posts response.');
        }

        $items = $decoded['results'] ?? null;
        if (!is_array($items)) {
            throw new DecodingException('Expected the posts response to contain a results array.');
        }

        $results = [];
        foreach ($items as $item) {
            if (!is_array($item)) {
                throw new DecodingException('Expected every item in the posts response to be a JSON object.');
            }
            $results[] = $this->parsePost($item);
        }

        $next = $this->redactUrl($decoded['next'] ?? null);
        $previous = $this->redactUrl($decoded['previous'] ?? null);

        $hasNext = !empty($next);
        $hasPrevious = !empty($previous);

        $nextPage = $hasNext ? $this->parsePageFromUrl($next) : null;
        $previousPage = $hasPrevious ? $this->parsePageFromUrl($previous) : null;

        return new PostsPage(
            results: $results,
            next: $next,
            previous: $previous,
            hasNext: $hasNext,
            hasPrevious: $hasPrevious,
            nextPage: $nextPage,
            previousPage: $previousPage,
        );
    }

    /**
     * Parse a single post item into a Post model.
     *
     * @param array<string, mixed> $item
     */
    private function parsePost(array $item): Post
    {
        $source = null;
        if (isset($item['source']) && is_array($item['source'])) {
            $source = new PostSource(
                title: $this->nullableString($item['source']['title'] ?? null),
                region: $this->nullableString($item['source']['region'] ?? null),
                domain: $this->nullableString($item['source']['domain'] ?? null),
                path: $this->nullableString($item['source']['path'] ?? null),
                createdAt: $this->parseDate($item['source']['created_at'] ?? null),
            );
        }

        $instruments = [];
        foreach ($item['instruments'] ?? [] as $inst) {
            if (is_array($inst)) {
                $instruments[] = new PostInstrument(
                    id: $this->nullableInt($inst['id'] ?? null),
                    code: $this->nullableString($inst['code'] ?? null),
                    slug: $this->nullableString($inst['slug'] ?? null),
                    title: $this->nullableString($inst['title'] ?? null),
                    volume: $this->nullableFloat($inst['volume'] ?? null),
                    change: $this->nullableFloat($inst['change'] ?? null),
                    currency: $this->nullableString($inst['currency'] ?? null),
                );
            }
        }

        $votes = null;
        if (isset($item['votes']) && is_array($item['votes'])) {
            $votes = new PostVotes(
                positive: $this->nullableInt($item['votes']['positive'] ?? null),
                negative: $this->nullableInt($item['votes']['negative'] ?? null),
                important: $this->nullableInt($item['votes']['important'] ?? null),
                liked: $this->nullableInt($item['votes']['liked'] ?? null),
                disliked: $this->nullableInt($item['votes']['disliked'] ?? null),
                lol: $this->nullableInt($item['votes']['lol'] ?? null),
                toxic: $this->nullableInt($item['votes']['toxic'] ?? null),
                comments: $this->nullableInt($item['votes']['comments'] ?? null),
                saved: $this->nullableInt($item['votes']['saved'] ?? null),
            );
        }

        $author = null;
        if (isset($item['author']) && is_array($item['author'])) {
            $author = new PostAuthor(
                id: $this->nullableInt($item['author']['id'] ?? null),
                name: $this->nullableString($item['author']['name'] ?? null),
                slug: $this->nullableString($item['author']['slug'] ?? null),
                url: $this->nullableString($item['author']['url'] ?? null),
                avatar: $this->nullableString($item['author']['avatar'] ?? null),
                twitter: $this->nullableString($item['author']['twitter'] ?? null),
                facebook: $this->nullableString($item['author']['facebook'] ?? null),
                linkedIn: $this->nullableString($item['author']['linkedin'] ?? null),
            );
        }

        $content = null;
        if (isset($item['content']) && is_array($item['content'])) {
            $content = new PostContent(
                raw: $this->nullableString($item['content']['raw'] ?? null),
            );
        }

        return new Post(
            id: $this->nullableInt($item['id'] ?? null),
            slug: $this->nullableString($item['slug'] ?? null),
            title: $this->nullableString($item['title'] ?? null),
            description: $this->nullableString($item['description'] ?? null),
            publishedAt: $this->parseDate($item['published_at'] ?? null),
            createdAt: $this->parseDate($item['created_at'] ?? null),
            kind: $this->nullableString($item['kind'] ?? null),
            source: $source,
            originalUrl: $this->nullableString($item['original_url'] ?? null),
            url: $this->nullableString($item['url'] ?? null),
            image: $this->nullableString($item['image'] ?? null),
            instruments: $instruments,
            votes: $votes,
            panicScore: $this->nullableInt($item['panic_score'] ?? null),
            panicScore1h: $this->nullableInt($item['panic_score_1h'] ?? null),
            author: $author,
            content: $content,
        );
    }

    /**
     * Parse an ISO-8601 date string into a DateTimeImmutable, or null.
     */
    private function parseDate(mixed $value): ?DateTimeImmutable
    {
        if ($value === null || !is_string($value) || $value === '') {
            return null;
        }

        $date = DateTimeImmutable::createFromFormat(DateTimeImmutable::ATOM, $value);
        if ($date === false) {
            $date = DateTimeImmutable::createFromFormat('Y-m-d\TH:i:s\Z', $value);
        }
        if ($date === false) {
            try {
                return new DateTimeImmutable($value);
            } catch (\Throwable) {
                return null;
            }
        }

        return $date;
    }

    private function nullableString(mixed $value): ?string
    {
        return is_string($value) ? $value : null;
    }

    private function nullableInt(mixed $value): ?int
    {
        if (is_int($value)) {
            return $value;
        }

        if (is_float($value) && is_finite($value) && floor($value) === $value) {
            return (int)$value;
        }

        return null;
    }

    private function nullableFloat(mixed $value): ?float
    {
        if (!is_int($value) && !is_float($value) && !(is_string($value) && is_numeric($value))) {
            return null;
        }

        $number = (float)$value;
        return is_finite($number) ? $number : null;
    }

    /**
     * Redact the auth_token parameter from a URL string.
     */
    private function redactUrl(mixed $url): string
    {
        if ($url === null || !is_string($url) || $url === '') {
            return '';
        }

        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['query'])) {
            return $this->redactString($url);
        }

        parse_str($parsed['query'], $query);
        if (isset($query['auth_token'])) {
            $query['auth_token'] = '[REDACTED]';
        }

        $queryString = http_build_query($query);
        $queryString = str_replace('%5BREDACTED%5D', '[REDACTED]', $queryString);
        $scheme = $parsed['scheme'] ?? 'https';
        $host = $parsed['host'] ?? '';
        $port = isset($parsed['port']) ? ':' . $parsed['port'] : '';
        $path = $parsed['path'] ?? '';
        $fragment = isset($parsed['fragment']) ? '#' . $parsed['fragment'] : '';

        return "{$scheme}://{$host}{$port}{$path}?{$queryString}{$fragment}";
    }

    /**
     * Extract the page number from a URL's query string.
     */
    private function parsePageFromUrl(string $url): ?int
    {
        $parsed = parse_url($url);
        if ($parsed === false || !isset($parsed['query'])) {
            return null;
        }

        parse_str($parsed['query'], $query);
        $page = $query['page'] ?? null;
        if ($page === null || !is_numeric($page)) {
            return null;
        }

        return (int)$page;
    }

    /**
     * Redact the token everywhere an API or transport error could expose it.
     * The API should never echo credentials, but server and proxy failures
     * are not a safe reason to leak one into an application's logs.
     */
    private function redactToken(mixed $value): mixed
    {
        if (is_array($value)) {
            foreach ($value as $key => $item) {
                $value[$key] = $this->redactToken($item);
            }
            return $value;
        }

        return is_string($value) ? $this->redactString($value) : $value;
    }

    private function redactString(string $value): string
    {
        $token = $this->config->authToken;
        if ($token === '') {
            return $value;
        }

        return str_replace(
            array_unique([$token, rawurlencode($token), urlencode($token)]),
            '[REDACTED]',
            $value,
        );
    }
}
