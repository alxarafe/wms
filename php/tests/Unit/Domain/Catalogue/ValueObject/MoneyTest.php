<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Catalogue\ValueObject\Money;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class MoneyTest extends TestCase
{
    public function testCreateMoney(): void
    {
        $money = new Money(100.50, 'EUR');
        self::assertSame(100.50, $money->amount());
        self::assertSame('EUR', $money->currency());
    }

    public function testZeroAmount(): void
    {
        $money = new Money(0.0, 'USD');
        self::assertSame(0.0, $money->amount());
    }

    public function testNegativeAmount(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(-1.0, 'EUR');
    }

    public function testInvalidCurrencyTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(10.0, 'EU');
    }

    public function testInvalidCurrencyTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(10.0, 'EURO');
    }

    public function testInvalidCurrencyLowercase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Money(10.0, 'eur');
    }

    public function testEquals(): void
    {
        $a = new Money(100.0, 'EUR');
        $b = new Money(100.0, 'EUR');
        self::assertTrue($a->equals($b));
    }

    public function testEqualsDifferentAmount(): void
    {
        $a = new Money(100.0, 'EUR');
        $b = new Money(200.0, 'EUR');
        self::assertFalse($a->equals($b));
    }

    public function testEqualsDifferentCurrency(): void
    {
        $a = new Money(100.0, 'EUR');
        $b = new Money(100.0, 'USD');
        self::assertFalse($a->equals($b));
    }
}
