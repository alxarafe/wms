<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory\ValueObject;

use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class QuantityTest extends TestCase
{
    public function testCreate(): void
    {
        $qty = Quantity::fromDecimal(10.0, 'EA');
        self::assertSame(10.0, $qty->value());
        self::assertSame('EA', $qty->unit());
    }

    public function testZeroValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromDecimal(0.0, 'EA');
    }

    public function testNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromDecimal(-5.0, 'EA');
    }

    public function testEmptyUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromDecimal(1.0, '');
    }

    public function testAdd(): void
    {
        $a = Quantity::fromDecimal(5.0, 'EA');
        $b = Quantity::fromDecimal(3.0, 'EA');
        $result = $a->add($b);
        self::assertSame(8.0, $result->value());
        self::assertSame('EA', $result->unit());
    }

    public function testAddDifferentUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $a = Quantity::fromDecimal(5.0, 'EA');
        $b = Quantity::fromDecimal(3.0, 'KG');
        $a->add($b);
    }

    public function testSubtract(): void
    {
        $a = Quantity::fromDecimal(5.0, 'EA');
        $b = Quantity::fromDecimal(3.0, 'EA');
        $result = $a->subtract($b);
        self::assertSame(2.0, $result->value());
    }

    public function testSubtractInsufficient(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $a = Quantity::fromDecimal(3.0, 'EA');
        $b = Quantity::fromDecimal(5.0, 'EA');
        $a->subtract($b);
    }

    public function testSubtractDifferentUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $a = Quantity::fromDecimal(5.0, 'EA');
        $b = Quantity::fromDecimal(3.0, 'KG');
        $a->subtract($b);
    }

    public function testEquals(): void
    {
        $a = Quantity::fromDecimal(10.0, 'EA');
        $b = Quantity::fromDecimal(10.0, 'EA');
        $c = Quantity::fromDecimal(5.0, 'EA');
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testScaledRoundTrip(): void
    {
        $qty = Quantity::fromScaled(30_500_001, 'EA');
        self::assertSame(30_500_001, $qty->scaledUnits());
        self::assertSame(30.500001, $qty->value());
    }

    public function testDecimalStringRoundTrip(): void
    {
        $qty = Quantity::fromDecimalString('30.50025', 'EA');
        self::assertSame(30_500_250, $qty->scaledUnits());
        self::assertSame('30.500250', $qty->toDecimalString());
        self::assertSame(30.50025, $qty->value());
    }

    public function testExactAdditionUsesScaledUnits(): void
    {
        $a = Quantity::fromDecimal(0.1, 'EA');
        $b = Quantity::fromDecimal(0.2, 'EA');
        // 0.1 + 0.2 no debe arrastrar el error binario del float.
        self::assertSame(0.3, $a->add($b)->value());
    }

    public function testRoundsHalfUpOnBoundary(): void
    {
        $qty = Quantity::fromDecimal(2.5000004, 'EA');
        self::assertSame(2_500_000, $qty->scaledUnits());
        self::assertSame(2.5, $qty->value());
    }

    public function testRejectsNonFinite(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromDecimal(NAN, 'EA');
    }

    public function testRejectsInfinite(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromDecimal(INF, 'EA');
    }

    public function testRejectsInvalidDecimalString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        Quantity::fromDecimalString('abc', 'EA');
    }
}
