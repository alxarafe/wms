<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory\ValueObject;

use Alxarafe\App\Domain\Inventory\ValueObject\BatchCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class BatchCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new BatchCode('BATCH-2026-001');
        self::assertSame('BATCH-2026-001', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BatchCode('');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new BatchCode(str_repeat('A', 31));
    }

    public function testEquals(): void
    {
        $a = new BatchCode('LOT001');
        $b = new BatchCode('LOT001');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new BatchCode('B001');
        self::assertSame('B001', (string) $code);
    }
}
