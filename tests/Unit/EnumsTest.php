<?php

declare(strict_types=1);

namespace Tigusigalpa\CryptoPanic\Tests\Unit;

use PHPUnit\Framework\TestCase;
use Tigusigalpa\CryptoPanic\Enums\Filter;
use Tigusigalpa\CryptoPanic\Enums\Kind;
use Tigusigalpa\CryptoPanic\Enums\PanicPeriod;
use Tigusigalpa\CryptoPanic\Enums\PanicSort;
use Tigusigalpa\CryptoPanic\Enums\Plan;

class EnumsTest extends TestCase
{
    public function test_plan_values(): void
    {
        self::assertSame('developer', Plan::Developer->value);
        self::assertSame('growth', Plan::Growth->value);
        self::assertSame('enterprise', Plan::Enterprise->value);
    }

    public function test_filter_values(): void
    {
        self::assertSame('rising', Filter::Rising->value);
        self::assertSame('hot', Filter::Hot->value);
        self::assertSame('bullish', Filter::Bullish->value);
        self::assertSame('bearish', Filter::Bearish->value);
        self::assertSame('important', Filter::Important->value);
        self::assertSame('saved', Filter::Saved->value);
        self::assertSame('lol', Filter::Lol->value);
    }

    public function test_kind_values(): void
    {
        self::assertSame('news', Kind::News->value);
        self::assertSame('media', Kind::Media->value);
        self::assertSame('all', Kind::All->value);
    }

    public function test_panic_period_values(): void
    {
        self::assertSame('1h', PanicPeriod::OneHour->value);
        self::assertSame('6h', PanicPeriod::SixHours->value);
        self::assertSame('24h', PanicPeriod::TwentyFourHours->value);
    }

    public function test_panic_sort_values(): void
    {
        self::assertSame('asc', PanicSort::Asc->value);
        self::assertSame('desc', PanicSort::Desc->value);
    }
}
