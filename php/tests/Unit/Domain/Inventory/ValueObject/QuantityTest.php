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
        $qty = new Quantity(10.0, 'EA');
        self::assertSame(10.0, $qty->value());
        self::assertSame('EA', $qty->unit());
    }

    public function testZeroValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Quantity(0.0, 'EA');
    }

    public function testNegativeValue(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Quantity(-5.0, 'EA');
    }

    public function testEmptyUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Quantity(1.0, '');
    }

    public function testAdd(): void
    {
        $a = new Quantity(5.0, 'EA');
        $b = new Quantity(3.0, 'EA');
        $result = $a->add($b);
        self::assertSame(8.0, $result->value());
        self::assertSame('EA', $result->unit());
    }

    public function testAddDifferentUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $a = new Quantity(5.0, 'EA');
        $b = new Quantity(3.0, 'KG');
        $a->add($b);
    }

    public function testSubtract(): void
    {
        $a = new Quantity(5.0, 'EA');
        $b = new Quantity(3.0, 'EA');
        $result = $a->subtract($b);
        self::assertSame(2.0, $result->value());
    }

    public function testSubtractInsufficient(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $a = new Quantity(3.0, 'EA');
        $b = new Quantity(5.0, 'EA');
        $a->subtract($b);
    }

    public function testSubtractDifferentUnit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $a = new Quantity(5.0, 'EA');
        $b = new Quantity(3.0, 'KG');
        $a->subtract($b);
    }

    public function testEquals(): void
    {
        $a = new Quantity(10.0, 'EA');
        $b = new Quantity(10.0, 'EA');
        $c = new Quantity(5.0, 'EA');
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
