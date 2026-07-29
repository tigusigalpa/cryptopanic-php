<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tigusigalpa\CryptoPanic\Exceptions\ApiException;
use Tigusigalpa\CryptoPanic\Exceptions\ConfigurationException;
use Tigusigalpa\CryptoPanic\Exceptions\CryptoPanicException;
use Tigusigalpa\CryptoPanic\Exceptions\DecodingException;
use Tigusigalpa\CryptoPanic\Exceptions\ForbiddenException;
use Tigusigalpa\CryptoPanic\Exceptions\RateLimitException;
use Tigusigalpa\CryptoPanic\Exceptions\ServerException;
use Tigusigalpa\CryptoPanic\Exceptions\TransportException;
use Tigusigalpa\CryptoPanic\Exceptions\UnauthorizedException;
use Tigusigalpa\CryptoPanic\Exceptions\ValidationException;

class ExceptionsTest extends TestCase
{
    public function test_all_exceptions_extend_base(): void
    {
        self::assertInstanceOf(CryptoPanicException::class, new ConfigurationException());
        self::assertInstanceOf(CryptoPanicException::class, new ValidationException());
        self::assertInstanceOf(CryptoPanicException::class, new TransportException());
        self::assertInstanceOf(CryptoPanicException::class, new DecodingException());

        self::assertInstanceOf(CryptoPanicException::class, new ApiException('test', 400));
        self::assertInstanceOf(CryptoPanicException::class, new UnauthorizedException());
        self::assertInstanceOf(CryptoPanicException::class, new ForbiddenException());
        self::assertInstanceOf(CryptoPanicException::class, new RateLimitException());
        self::assertInstanceOf(CryptoPanicException::class, new ServerException());
    }

    public function test_api_exception_inherits_from_base(): void
    {
        self::assertInstanceOf(ApiException::class, new UnauthorizedException());
        self::assertInstanceOf(ApiException::class, new ForbiddenException());
        self::assertInstanceOf(ApiException::class, new RateLimitException());
        self::assertInstanceOf(ApiException::class, new ServerException());
    }

    public function test_unauthorized_status_code(): void
    {
        $e = new UnauthorizedException();
        self::assertSame(401, $e->getStatusCode());
        self::assertSame(401, $e->getCode());
    }

    public function test_forbidden_status_code(): void
    {
        $e = new ForbiddenException();
        self::assertSame(403, $e->getStatusCode());
    }

    public function test_rate_limit_status_code_and_retry_after(): void
    {
        $e = new RateLimitException(retryAfter: '5');
        self::assertSame(429, $e->getStatusCode());
        self::assertSame('5', $e->retryAfter);
    }

    public function test_server_error_status_code(): void
    {
        $e = new ServerException();
        self::assertSame(500, $e->getStatusCode());
    }

    public function test_api_exception_carries_response_body(): void
    {
        $body = ['error' => 'test error'];
        $e = new ApiException('test', 400, $body, 'req-123');
        self::assertSame(400, $e->getStatusCode());
        self::assertSame($body, $e->responseBody);
        self::assertSame('req-123', $e->requestId);
    }
}
