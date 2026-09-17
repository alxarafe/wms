<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\WarehouseCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WarehouseCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new WarehouseCode('WH01');
        self::assertSame('WH01', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WarehouseCode('');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new WarehouseCode(str_repeat('A', 11));
    }

    public function testEquals(): void
    {
        $a = new WarehouseCode('MAIN');
        $b = new WarehouseCode('MAIN');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new WarehouseCode('WH01');
        self::assertSame('WH01', (string) $code);
    }
}
