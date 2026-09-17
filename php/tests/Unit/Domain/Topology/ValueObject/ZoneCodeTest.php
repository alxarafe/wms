<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\ZoneCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ZoneCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new ZoneCode('PICKING');
        self::assertSame('PICKING', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ZoneCode('');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ZoneCode(str_repeat('A', 21));
    }

    public function testEquals(): void
    {
        $a = new ZoneCode('Z01');
        $b = new ZoneCode('Z01');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new ZoneCode('BULK');
        self::assertSame('BULK', (string) $code);
    }
}
