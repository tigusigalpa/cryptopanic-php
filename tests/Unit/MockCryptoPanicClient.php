<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\CryptoPanicConfig;
use Tigusigalpa\CryptoPanic\Transport\HttpResponse;

/**
 * Mock CryptoPanicClient that returns pre-set responses instead of
 * making real HTTP requests. Responses are returned in sequence for
 * retry testing.
 *
 * @property HttpResponse[] $mockResponses
 */
final class MockCryptoPanicClient extends CryptoPanicClient
{
    private array $mockResponses;
    private int $callIndex = 0;

    /**
     * @param HttpResponse[] $mockResponses
     */
    public function __construct(CryptoPanicConfig $config, array $mockResponses)
    {
        parent::__construct($config);
        $this->mockResponses = $mockResponses;
    }

    /**
     * Override the URL and transport to return mock responses.
     * We use reflection to access the private doGet/doGetRaw methods
     * indirectly by intercepting at the transport level.
     *
     * Instead of complex reflection, we override the public methods
     * to inject mock responses directly.
     */

    /**
     * Get the next mock response.
     */
    private function nextResponse(): HttpResponse
    {
        $response = $this->mockResponses[$this->callIndex] ??
            $this->mockResponses[count($this->mockResponses) - 1];
        $this->callIndex++;
        return $response;
    }

    /**
     * Override posts to use mock response.
     */
    public function posts(\Tigusigalpa\CryptoPanic\PostsQuery $query): \Tigusigalpa\CryptoPanic\Models\PostsPage
    {
        $query->validate();

        $response = $this->nextResponse();

        // Handle retry
        while ($this->shouldRetryStatus($response->statusCode) && $this->hasMoreRetries()) {
            $this->sleep(0.0);
            $response = $this->nextResponse();
        }

        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            $decoded = json_decode($response->body, true);
            return $this->parsePostsPagePublic($decoded);
        }

        $this->throwForStatusPublic($response);
    }

    /**
     * Override portfolio to use mock response.
     */
    public function portfolio(): \Tigusigalpa\CryptoPanic\Models\PortfolioResponse
    {
        $response = $this->nextResponse();

        while ($this->shouldRetryStatus($response->statusCode) && $this->hasMoreRetries()) {
            $this->sleep(0.0);
            $response = $this->nextResponse();
        }

        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            $decoded = json_decode($response->body, true);
            return new \Tigusigalpa\CryptoPanic\Models\PortfolioResponse(raw: $decoded);
        }

        $this->throwForStatusPublic($response);
    }

    /**
     * Override postsRss to use mock response.
     */
    public function postsRss(\Tigusigalpa\CryptoPanic\PostsQuery $query): \Tigusigalpa\CryptoPanic\Models\RSSResponse
    {
        $query->validate();
        $response = $this->nextResponse();

        while ($this->shouldRetryStatus($response->statusCode) && $this->hasMoreRetries()) {
            $this->sleep(0.0);
            $response = $this->nextResponse();
        }

        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            return new \Tigusigalpa\CryptoPanic\Models\RSSResponse(body: $response->body);
        }

        $this->throwForStatusPublic($response);
    }

    /**
     * Override newsRss to use mock response.
     */
    public function newsRss(): \Tigusigalpa\CryptoPanic\Models\RSSResponse
    {
        $response = $this->nextResponse();

        while ($this->shouldRetryStatus($response->statusCode) && $this->hasMoreRetries()) {
            $this->sleep(0.0);
            $response = $this->nextResponse();
        }

        if ($response->statusCode >= 200 && $response->statusCode < 300) {
            return new \Tigusigalpa\CryptoPanic\Models\RSSResponse(body: $response->body);
        }

        $this->throwForStatusPublic($response);
    }

    private function shouldRetryStatus(int $statusCode): bool
    {
        return in_array($statusCode, [429, 500, 502, 503], true);
    }

    private function hasMoreRetries(): bool
    {
        return $this->callIndex < count($this->mockResponses);
    }

    private function sleep(float $delay): void
    {
        // No-op for tests
    }

    /**
     * @return never
     */
    private function throwForStatusPublic(HttpResponse $response): never
    {
        $decoded = json_decode($response->body, true);
        $decodedArr = is_array($decoded) ? $decoded : [];
        $message = is_array($decoded)
            ? (string)($decoded['message'] ?? $decoded['msg'] ?? $decoded['error'] ?? 'Unknown error')
            : 'Unknown error';
        $requestId = $response->getHeader('x-request-id');

        throw match ($response->statusCode) {
            401 => new \Tigusigalpa\CryptoPanic\Exceptions\UnauthorizedException($message, $decodedArr, $requestId),
            403 => new \Tigusigalpa\CryptoPanic\Exceptions\ForbiddenException($message, $decodedArr, $requestId),
            429 => new \Tigusigalpa\CryptoPanic\Exceptions\RateLimitException(
                $message,
                $decodedArr,
                $response->getHeader('retry-after'),
                $requestId,
            ),
            500 => new \Tigusigalpa\CryptoPanic\Exceptions\ServerException($message, $decodedArr, $requestId),
            default => new \Tigusigalpa\CryptoPanic\Exceptions\ApiException(
                $message,
                $response->statusCode,
                $decodedArr,
                $requestId,
            ),
        };
    }

    /**
     * Parse posts page from decoded JSON.
     *
     * @param array<string, mixed> $decoded
     */
    private function parsePostsPagePublic(array $decoded): \Tigusigalpa\CryptoPanic\Models\PostsPage
    {
        $results = [];
        foreach ($decoded['results'] ?? [] as $item) {
            if (!is_array($item)) {
                continue;
            }
            $results[] = $this->parsePostPublic($item);
        }

        $next = $this->redactUrlPublic($decoded['next'] ?? null);
        $previous = $this->redactUrlPublic($decoded['previous'] ?? null);

        $hasNext = !empty($next);
        $hasPrevious = !empty($previous);

        $nextPage = $hasNext ? $this->parsePageFromUrlPublic($next) : null;
        $previousPage = $hasPrevious ? $this->parsePageFromUrlPublic($previous) : null;

        return new \Tigusigalpa\CryptoPanic\Models\PostsPage(
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
     * @param array<string, mixed> $item
     */
    private function parsePostPublic(array $item): \Tigusigalpa\CryptoPanic\Models\Post
    {
        $source = null;
        if (isset($item['source']) && is_array($item['source'])) {
            $source = new \Tigusigalpa\CryptoPanic\Models\PostSource(
                title: $item['source']['title'] ?? null,
                region: $item['source']['region'] ?? null,
                domain: $item['source']['domain'] ?? null,
                path: $item['source']['path'] ?? null,
                createdAt: $this->parseDatePublic($item['source']['created_at'] ?? null),
            );
        }

        $instruments = [];
        foreach ($item['instruments'] ?? [] as $inst) {
            if (is_array($inst)) {
                $instruments[] = new \Tigusigalpa\CryptoPanic\Models\PostInstrument(
                    id: $inst['id'] ?? null,
                    code: $inst['code'] ?? null,
                    slug: $inst['slug'] ?? null,
                    title: $inst['title'] ?? null,
                    volume: isset($inst['volume']) ? (float)$inst['volume'] : null,
                    change: isset($inst['change']) ? (float)$inst['change'] : null,
                    currency: $inst['currency'] ?? null,
                );
            }
        }

        $votes = null;
        if (isset($item['votes']) && is_array($item['votes'])) {
            $votes = new \Tigusigalpa\CryptoPanic\Models\PostVotes(
                positive: $item['votes']['positive'] ?? null,
                negative: $item['votes']['negative'] ?? null,
                important: $item['votes']['important'] ?? null,
                liked: $item['votes']['liked'] ?? null,
                disliked: $item['votes']['disliked'] ?? null,
                lol: $item['votes']['lol'] ?? null,
                toxic: $item['votes']['toxic'] ?? null,
                comments: $item['votes']['comments'] ?? null,
                saved: $item['votes']['saved'] ?? null,
            );
        }

        $author = null;
        if (isset($item['author']) && is_array($item['author'])) {
            $author = new \Tigusigalpa\CryptoPanic\Models\PostAuthor(
                id: $item['author']['id'] ?? null,
                name: $item['author']['name'] ?? null,
                slug: $item['author']['slug'] ?? null,
                url: $item['author']['url'] ?? null,
                avatar: $item['author']['avatar'] ?? null,
                twitter: $item['author']['twitter'] ?? null,
                facebook: $item['author']['facebook'] ?? null,
                linkedIn: $item['author']['linkedin'] ?? null,
            );
        }

        $content = null;
        if (isset($item['content']) && is_array($item['content'])) {
            $content = new \Tigusigalpa\CryptoPanic\Models\PostContent(
                raw: $item['content']['raw'] ?? null,
            );
        }

        return new \Tigusigalpa\CryptoPanic\Models\Post(
            id: $item['id'] ?? null,
            slug: $item['slug'] ?? null,
            title: $item['title'] ?? null,
            description: $item['description'] ?? null,
            publishedAt: $this->parseDatePublic($item['published_at'] ?? null),
            createdAt: $this->parseDatePublic($item['created_at'] ?? null),
            kind: $item['kind'] ?? null,
            source: $source,
            originalUrl: $item['original_url'] ?? null,
            url: $item['url'] ?? null,
            image: $item['image'] ?? null,
            instruments: $instruments,
            votes: $votes,
            panicScore: $item['panic_score'] ?? null,
            panicScore1h: $item['panic_score_1h'] ?? null,
            author: $author,
            content: $content,
        );
    }

    private function parseDatePublic(mixed $value): ?\DateTimeImmutable
    {
        if ($value === null || !is_string($value) || $value === '') {
            return null;
        }

        try {
            return new \DateTimeImmutable($value);
        } catch (\Throwable) {
            return null;
        }
    }

    private function redactUrlPublic(mixed $url): string
    {
        if ($url === null || !is_string($url) || $url === '') {
            return '';
        }

        $parsed = parse_url($url);
        if (!isset($parsed['query'])) {
            return $url;
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

        return "{$scheme}://{$host}{$port}{$path}?{$queryString}";
    }

    private function parsePageFromUrlPublic(string $url): ?int
    {
        $parsed = parse_url($url);
        if (!isset($parsed['query'])) {
            return null;
        }

        parse_str($parsed['query'], $query);
        $page = $query['page'] ?? null;
        if ($page === null || !is_numeric($page)) {
            return null;
        }

        return (int)$page;
    }
}
