<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Catalogue\ValueObject\Sku;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SkuTest extends TestCase
{
    public function testCreate(): void
    {
        $sku = new Sku('WIDGET-001');
        self::assertSame('WIDGET-001', $sku->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sku('');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sku(str_repeat('A', 51));
    }

    public function testEquals(): void
    {
        $a = new Sku('ABC');
        $b = new Sku('ABC');
        $c = new Sku('XYZ');
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $sku = new Sku('TEST');
        self::assertSame('TEST', (string) $sku);
    }
}
