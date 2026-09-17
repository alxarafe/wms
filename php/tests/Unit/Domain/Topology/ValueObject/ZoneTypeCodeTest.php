<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\ZoneTypeCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ZoneTypeCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new ZoneTypeCode('PICKING');
        self::assertSame('PICKING', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ZoneTypeCode('');
    }

    public function testTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ZoneTypeCode('A');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ZoneTypeCode(str_repeat('A', 21));
    }

    public function testLowercase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ZoneTypeCode('picking');
    }

    public function testWithUnderscore(): void
    {
        $code = new ZoneTypeCode('BULK_STORAGE');
        self::assertSame('BULK_STORAGE', $code->value());
    }

    public function testEquals(): void
    {
        $a = new ZoneTypeCode('RESERVE');
        $b = new ZoneTypeCode('RESERVE');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new ZoneTypeCode('STAGING');
        self::assertSame('STAGING', (string) $code);
    }
}
