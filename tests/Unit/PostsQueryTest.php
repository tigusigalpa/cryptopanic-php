<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use DateTimeImmutable;
use PHPUnit\Framework\TestCase;
use Tigusigalpa\CryptoPanic\CryptoPanicConfig;
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Enums\Kind;
use Tigusigalpa\CryptoPanic\Enums\PanicPeriod;
use Tigusigalpa\CryptoPanic\Enums\PanicSort;
use Tigusigalpa\CryptoPanic\Enums\Plan;
use Tigusigalpa\CryptoPanic\Exceptions\ConfigurationException;
use Tigusigalpa\CryptoPanic\Exceptions\ValidationException;
use Tigusigalpa\CryptoPanic\PostsQuery;

class PostsQueryTest extends TestCase
{
    public function test_to_query_array_full(): void
    {
        $query = new PostsQuery(
            public: true,
            currencies: ['BTC', 'ETH'],
            regions: 'en',
            filter: Filter::Rising,
            kind: Kind::News,
            following: false,
            size: 20,
            page: 2,
            withContent: true,
            search: 'bitcoin',
        );

        $params = $query->toQueryArray();

        self::assertSame('true', $params['public']);
        self::assertSame('BTC,ETH', $params['currencies']);
        self::assertSame('en', $params['regions']);
        self::assertSame('rising', $params['filter']);
        self::assertSame('news', $params['kind']);
        self::assertSame('false', $params['following']);
        self::assertSame('20', $params['size']);
        self::assertSame('2', $params['page']);
        self::assertSame('true', $params['with_content']);
        self::assertSame('bitcoin', $params['search']);
    }

    public function test_to_query_array_empty(): void
    {
        $query = new PostsQuery();
        self::assertEmpty($query->toQueryArray());
    }

    public function test_to_query_array_last_pull(): void
    {
        $date = new DateTimeImmutable('2025-01-15T10:30:00+00:00');
        $query = new PostsQuery(lastPull: $date);
        $params = $query->toQueryArray();
        self::assertSame($date->format(DateTimeImmutable::ATOM), $params['last_pull']);
    }

    public function test_validate_panic_sort_without_period_throws(): void
    {
        $query = new PostsQuery(panicSort: PanicSort::Asc);
        $this->expectException(ValidationException::class);
        $query->validate();
    }

    public function test_validate_panic_sort_with_period_ok(): void
    {
        $query = new PostsQuery(panicSort: PanicSort::Asc, panicPeriod: PanicPeriod::OneHour);
        $query->validate();
        $this->addToAssertionCount(1);
    }

    public function test_validate_size_out_of_range_throws(): void
    {
        $this->expectException(ValidationException::class);
        $query = new PostsQuery(size: 0);
        $query->validate();
    }

    public function test_validate_size_above_max_throws(): void
    {
        $this->expectException(ValidationException::class);
        $query = new PostsQuery(size: 51);
        $query->validate();
    }

    public function test_validate_size_valid(): void
    {
        $query = new PostsQuery(size: 50);
        $query->validate();
        $this->addToAssertionCount(1);
    }

    public function test_validate_page_out_of_range_throws(): void
    {
        $this->expectException(ValidationException::class);
        $query = new PostsQuery(page: 0);
        $query->validate();
    }

    public function test_validate_page_above_max_throws(): void
    {
        $this->expectException(ValidationException::class);
        $query = new PostsQuery(page: 51);
        $query->validate();
    }

    public function test_validate_page_valid(): void
    {
        $query = new PostsQuery(page: 1);
        $query->validate();
        $this->addToAssertionCount(1);
    }

    public function test_with_builder(): void
    {
        $query = new PostsQuery(currencies: ['BTC']);
        $newQuery = $query->with(['currencies' => ['ETH'], 'page' => 3]);

        self::assertSame(['BTC'], $query->currencies);
        self::assertSame(['ETH'], $newQuery->currencies);
        self::assertSame(3, $newQuery->page);
    }

    public function test_custom_filter_string(): void
    {
        $query = new PostsQuery(filter: 'custom_filter');
        $params = $query->toQueryArray();
        self::assertSame('custom_filter', $params['filter']);
    }

    public function test_custom_kind_string(): void
    {
        $query = new PostsQuery(kind: 'custom_kind');
        $params = $query->toQueryArray();
        self::assertSame('custom_kind', $params['kind']);
    }
}
