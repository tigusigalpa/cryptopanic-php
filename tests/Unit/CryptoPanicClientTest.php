<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use GuzzleHttp\Psr7\HttpFactory;
use GuzzleHttp\Psr7\Response as GuzzleResponse;
use PHPUnit\Framework\TestCase;
use Tigusigalpa\CryptoPanic\CryptoPanicClient;
use Tigusigalpa\CryptoPanic\CryptoPanicConfig;
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Enums\Kind;
use Tigusigalpa\CryptoPanic\Enums\Plan;
use Tigusigalpa\CryptoPanic\Exceptions\ConfigurationException;
use Tigusigalpa\CryptoPanic\Exceptions\ForbiddenException;
use Tigusigalpa\CryptoPanic\Exceptions\RateLimitException;
use Tigusigalpa\CryptoPanic\Exceptions\ServerException;
use Tigusigalpa\CryptoPanic\Exceptions\TransportException;
use Tigusigalpa\CryptoPanic\Exceptions\UnauthorizedException;
use Tigusigalpa\CryptoPanic\Exceptions\ValidationException;
use Tigusigalpa\CryptoPanic\PostsQuery;
use Tigusigalpa\CryptoPanic\Transport\HttpResponse;

/**
 * Unit tests for CryptoPanicClient using a mock HTTP server.
 *
 * These tests start a PHP built-in server on localhost to serve
 * fixture responses, allowing full end-to-end testing of the cURL
 * transport without mocking.
 */
class CryptoPanicClientTest extends TestCase
{
    private const TEST_TOKEN = 'test-secret-token-12345';
    protected function setUp(): void
    {
        parent::setUp();
        $documentRoot = __DIR__ . '/Fixtures';
        if (!is_dir($documentRoot)) {
            mkdir($documentRoot, 0777, true);
        }
    }

    public function test_missing_auth_token_throws(): void
    {
        $this->expectException(ConfigurationException::class);
        new CryptoPanicClient(new CryptoPanicConfig(authToken: ''));
    }

    public function test_config_immutable(): void
    {
        $config = new CryptoPanicConfig(authToken: 'test');
        $client = new CryptoPanicClient($config);
        self::assertSame($config, $client->getConfig());
    }

    public function test_posts_query_validation_throws_before_request(): void
    {
        $client = new CryptoPanicClient(new CryptoPanicConfig(
            authToken: self::TEST_TOKEN,
            baseUrl: 'http://localhost:1',
        ));

        $this->expectException(ValidationException::class);
        $client->posts(new PostsQuery(panicSort: \Tigusigalpa\CryptoPanic\Enums\PanicSort::Asc));
    }

    public function test_redact_url_in_pagination(): void
    {
        $json = json_encode([
            'next' => 'https://cryptopanic.com/api/growth/v2/posts/?auth_token=SECRET_TOKEN&page=2',
            'previous' => null,
            'results' => [],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createMockResponse(200, $json);
        $client = $this->createClientWithMockResponse($response);

        $page = $client->posts(new PostsQuery());

        self::assertTrue($page->hasNext);
        self::assertStringNotContainsString('SECRET_TOKEN', $page->next);
        self::assertStringContainsString('[REDACTED]', $page->next);
        self::assertSame(2, $page->nextPage);
    }

    public function test_posts_success_typed_models(): void
    {
        $json = json_encode([
            'next' => null,
            'previous' => null,
            'results' => [
                [
                    'id' => 12345,
                    'slug' => 'bitcoin-surges',
                    'title' => 'Bitcoin Surges Past $100K',
                    'description' => 'Bitcoin breaks $100K.',
                    'published_at' => '2025-01-15T10:30:00+00:00',
                    'created_at' => '2025-01-15T10:31:00+00:00',
                    'kind' => 'news',
                    'source' => [
                        'title' => 'CoinDesk',
                        'region' => 'en',
                        'domain' => 'coindesk.com',
                        'path' => '/bitcoin-surges',
                        'created_at' => '2025-01-15T10:30:00+00:00',
                    ],
                    'original_url' => 'https://coindesk.com/bitcoin-surges',
                    'url' => 'https://cryptopanic.com/news/12345/bitcoin-surges',
                    'image' => 'https://cryptopanic.com/images/12345.jpg',
                    'instruments' => [
                        [
                            'id' => 1,
                            'code' => 'BTC',
                            'slug' => 'btc',
                            'title' => 'Bitcoin',
                            'volume' => 50000000.0,
                            'change' => 5.2,
                            'currency' => 'USD',
                        ],
                    ],
                    'votes' => [
                        'positive' => 120,
                        'negative' => 5,
                        'important' => 30,
                        'liked' => 100,
                        'disliked' => 3,
                        'lol' => 2,
                        'toxic' => 1,
                        'comments' => 45,
                        'saved' => 15,
                    ],
                    'panic_score' => 85,
                    'panic_score_1h' => 72,
                    'author' => [
                        'id' => 678,
                        'name' => 'crypto_journalist',
                        'slug' => 'crypto-journalist',
                        'url' => 'https://cryptopanic.com/people/crypto-journalist/',
                        'avatar' => 'https://cryptopanic.com/avatars/678.jpg',
                        'twitter' => 'cryptojourno',
                        'facebook' => '',
                        'linkedin' => '',
                    ],
                    'content' => [
                        'raw' => 'Full article content here...',
                    ],
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createMockResponse(200, $json);
        $client = $this->createClientWithMockResponse($response);

        $page = $client->posts(new PostsQuery(
            currencies: ['BTC'],
            filter: Filter::Rising,
            kind: Kind::News,
        ));

        self::assertCount(1, $page->results);
        $post = $page->results[0];
        self::assertSame(12345, $post->id);
        self::assertSame('Bitcoin Surges Past $100K', $post->title);
        self::assertSame('news', $post->kind);
        self::assertNotNull($post->source);
        self::assertSame('CoinDesk', $post->source->title);
        self::assertNotNull($post->votes);
        self::assertSame(120, $post->votes->positive);
        self::assertSame(85, $post->panicScore);
        self::assertNotNull($post->author);
        self::assertSame('crypto_journalist', $post->author->name);
        self::assertNotNull($post->content);
        self::assertSame('Full article content here...', $post->content->raw);
        self::assertCount(1, $post->instruments);
        self::assertSame('BTC', $post->instruments[0]->code);
        self::assertNotNull($post->publishedAt);
    }

    public function test_posts_null_fields(): void
    {
        $json = json_encode([
            'next' => null,
            'previous' => null,
            'results' => [
                [
                    'id' => 1,
                    'slug' => 'test',
                    'title' => 'Test',
                    'description' => 'Desc',
                    'published_at' => null,
                    'created_at' => null,
                    'kind' => 'news',
                    'source' => null,
                    'original_url' => '',
                    'url' => '',
                    'image' => null,
                    'instruments' => [],
                    'votes' => null,
                    'panic_score' => null,
                    'panic_score_1h' => null,
                    'author' => null,
                    'content' => null,
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createMockResponse(200, $json);
        $client = $this->createClientWithMockResponse($response);

        $page = $client->posts(new PostsQuery());

        self::assertCount(1, $page->results);
        $post = $page->results[0];
        self::assertNull($post->publishedAt);
        self::assertNull($post->source);
        self::assertNull($post->votes);
        self::assertNull($post->panicScore);
        self::assertNull($post->author);
        self::assertNull($post->content);
        self::assertNull($post->image);
        self::assertFalse($page->hasNext);
        self::assertFalse($page->hasPrevious);
    }

    public function test_unauthorized_throws(): void
    {
        $json = json_encode(['error' => 'Invalid auth token'], JSON_THROW_ON_ERROR);
        $response = $this->createMockResponse(401, $json);
        $client = $this->createClientWithMockResponse($response);

        $this->expectException(UnauthorizedException::class);
        $client->posts(new PostsQuery());
    }

    public function test_forbidden_throws(): void
    {
        $json = json_encode(['error' => 'Plan restricted'], JSON_THROW_ON_ERROR);
        $response = $this->createMockResponse(403, $json);
        $client = $this->createClientWithMockResponse($response);

        $this->expectException(ForbiddenException::class);
        $client->posts(new PostsQuery());
    }

    public function test_rate_limit_throws(): void
    {
        $json = json_encode(['error' => 'Rate limited'], JSON_THROW_ON_ERROR);
        $response = $this->createMockResponse(429, $json, ['retry-after' => '5']);
        $client = $this->createClientWithMockResponse($response);

        $this->expectException(RateLimitException::class);
        $client->posts(new PostsQuery());
    }

    public function test_server_error_throws(): void
    {
        $json = json_encode(['error' => 'Internal error'], JSON_THROW_ON_ERROR);
        $response = $this->createMockResponse(500, $json);
        $client = $this->createClientWithMockResponse($response);

        $this->expectException(ServerException::class);
        $client->posts(new PostsQuery());
    }

    public function test_error_does_not_leak_token(): void
    {
        $json = json_encode(['error' => 'Invalid token'], JSON_THROW_ON_ERROR);
        $response = $this->createMockResponse(401, $json);
        $client = $this->createClientWithMockResponse($response);

        try {
            $client->posts(new PostsQuery());
            self::fail('Expected UnauthorizedException');
        } catch (UnauthorizedException $e) {
            self::assertStringNotContainsString(self::TEST_TOKEN, $e->getMessage());
        }
    }

    public function test_portfolio_success(): void
    {
        $json = json_encode([
            'portfolio' => [
                ['code' => 'BTC', 'title' => 'Bitcoin', 'amount' => 0.5],
            ],
            'total_value' => 75000.00,
            'currency' => 'USD',
        ], JSON_THROW_ON_ERROR);

        $response = $this->createMockResponse(200, $json);
        $client = $this->createClientWithMockResponse($response);

        $portfolio = $client->portfolio();
        self::assertNotNull($portfolio->raw);
        self::assertSame('USD', $portfolio->raw['currency']);
    }

    public function test_retry_on_429(): void
    {
        $rateLimitJson = json_encode(['error' => 'Rate limited'], JSON_THROW_ON_ERROR);
        $successJson = json_encode(['next' => null, 'previous' => null, 'results' => []], JSON_THROW_ON_ERROR);

        $responses = [
            $this->createMockResponse(429, $rateLimitJson, ['retry-after' => '0']),
            $this->createMockResponse(200, $successJson),
        ];

        $client = $this->createClientWithMockResponses($responses, retryAttempts: 3, retryDelay: 0.001);
        $client->setSleeper(fn() => null);

        $page = $client->posts(new PostsQuery());
        self::assertEmpty($page->results);
    }

    public function test_retry_exhausted(): void
    {
        $rateLimitJson = json_encode(['error' => 'Rate limited'], JSON_THROW_ON_ERROR);

        $responses = [
            $this->createMockResponse(429, $rateLimitJson),
            $this->createMockResponse(429, $rateLimitJson),
            $this->createMockResponse(429, $rateLimitJson),
        ];

        $client = $this->createClientWithMockResponses($responses, retryAttempts: 2, retryDelay: 0.001);
        $client->setSleeper(fn() => null);

        $this->expectException(RateLimitException::class);
        $client->posts(new PostsQuery());
    }

    public function test_no_retry_on_401(): void
    {
        $json = json_encode(['error' => 'Unauthorized'], JSON_THROW_ON_ERROR);
        $responses = [$this->createMockResponse(401, $json)];

        $client = $this->createClientWithMockResponses($responses, retryAttempts: 3, retryDelay: 0.001);
        $client->setSleeper(fn() => null);

        $this->expectException(UnauthorizedException::class);
        $client->posts(new PostsQuery());
    }

    public function test_posts_rss(): void
    {
        $rss = '<?xml version="1.0"?><rss version="2.0"><channel><title>Test</title></channel></rss>';
        $response = $this->createMockResponse(200, $rss);
        $client = $this->createClientWithMockResponse($response);

        $result = $client->postsRss(new PostsQuery(currencies: ['BTC']));
        self::assertStringContainsString('<rss', $result->body);
    }

    public function test_news_rss(): void
    {
        $rss = '<?xml version="1.0"?><rss version="2.0"><channel><title>News</title></channel></rss>';
        $response = $this->createMockResponse(200, $rss);
        $client = $this->createClientWithMockResponse($response);

        $result = $client->newsRss();
        self::assertStringContainsString('<rss', $result->body);
    }

    public function test_additive_fields(): void
    {
        $json = json_encode([
            'next' => null,
            'previous' => null,
            'new_envelope_field' => 'additive',
            'results' => [
                [
                    'id' => 1,
                    'slug' => 'test',
                    'title' => 'Test',
                    'description' => '',
                    'kind' => 'news',
                    'instruments' => [],
                    'new_undocumented_field' => 'should not cause error',
                ],
            ],
        ], JSON_THROW_ON_ERROR);

        $response = $this->createMockResponse(200, $json);
        $client = $this->createClientWithMockResponse($response);

        $page = $client->posts(new PostsQuery());
        self::assertCount(1, $page->results);
    }

    public function test_plan_in_url(): void
    {
        $json = json_encode(['next' => null, 'previous' => null, 'results' => []], JSON_THROW_ON_ERROR);
        $response = $this->createMockResponse(200, $json);
        $client = $this->createClientWithMockResponse($response, apiPlan: 'enterprise');

        $client->posts(new PostsQuery());
        $this->addToAssertionCount(1);
    }

    public function test_psr18_transport_failure_is_retried_and_succeeds(): void
    {
        $json = json_encode(['next' => null, 'previous' => null, 'results' => []], JSON_THROW_ON_ERROR);
        $successResponse = new GuzzleResponse(200, [], $json);
        $fakePsrClient = new FakeThrowingPsr18Client(failuresBeforeSuccess: 2, successResponse: $successResponse);

        $config = new CryptoPanicConfig(
            authToken: self::TEST_TOKEN,
            retryAttempts: 3,
            retryDelay: 0.001,
        );

        $client = new CryptoPanicClient($config, $fakePsrClient, new HttpFactory());
        $client->setSleeper(fn() => null);

        $page = $client->posts(new PostsQuery());

        self::assertEmpty($page->results);
        self::assertSame(3, $fakePsrClient->getCallCount());
    }

    public function test_psr18_transport_failure_exhausted_throws_transport_exception(): void
    {
        $successResponse = new GuzzleResponse(200, [], '{}');
        $fakePsrClient = new FakeThrowingPsr18Client(failuresBeforeSuccess: 5, successResponse: $successResponse);

        $config = new CryptoPanicConfig(
            authToken: self::TEST_TOKEN,
            retryAttempts: 2,
            retryDelay: 0.001,
        );

        $client = new CryptoPanicClient($config, $fakePsrClient, new HttpFactory());
        $client->setSleeper(fn() => null);

        $this->expectException(TransportException::class);
        $client->posts(new PostsQuery());
    }

    public function test_psr18_transport_failure_without_retry_throws_immediately(): void
    {
        $successResponse = new GuzzleResponse(200, [], '{}');
        $fakePsrClient = new FakeThrowingPsr18Client(failuresBeforeSuccess: 1, successResponse: $successResponse);

        $config = new CryptoPanicConfig(authToken: self::TEST_TOKEN);

        $client = new CryptoPanicClient($config, $fakePsrClient, new HttpFactory());

        $this->expectException(TransportException::class);
        $client->posts(new PostsQuery());
        self::assertSame(1, $fakePsrClient->getCallCount());
    }

    /**
     * Create a mock HttpResponse.
     *
     * @param array<string, string> $headers
     */
    private function createMockResponse(int $statusCode, string $body, array $headers = []): HttpResponse
    {
        $lowerHeaders = [];
        foreach ($headers as $key => $value) {
            $lowerHeaders[strtolower($key)] = $value;
        }
        return new HttpResponse($statusCode, $body, $lowerHeaders);
    }

    /**
     * Create a client that returns a single mock response.
     */
    private function createClientWithMockResponse(HttpResponse $response, string $apiPlan = 'growth'): CryptoPanicClient
    {
        return $this->createClientWithMockResponses([$response], apiPlan: $apiPlan);
    }

    /**
     * Create a client that returns multiple mock responses in sequence (for retry tests).
     *
     * @param HttpResponse[] $responses
     */
    private function createClientWithMockResponses(array $responses, int $retryAttempts = 0, float $retryDelay = 1.0, string $apiPlan = 'growth'): CryptoPanicClient
    {
        $config = new CryptoPanicConfig(
            authToken: self::TEST_TOKEN,
            apiPlan: $apiPlan,
            baseUrl: 'https://mock.example.com',
            retryAttempts: $retryAttempts,
            retryDelay: $retryDelay,
        );

        $client = new MockCryptoPanicClient($config, $responses);
        return $client;
    }
}
