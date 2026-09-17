<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\LocationCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LocationCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new LocationCode('WH01-A01-01-01');
        self::assertSame('WH01-A01-01-01', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LocationCode('');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new LocationCode(str_repeat('A', 51));
    }

    public function testEquals(): void
    {
        $a = new LocationCode('WH01-A01-01-01');
        $b = new LocationCode('WH01-A01-01-01');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new LocationCode('WH01-A01-01-01');
        self::assertSame('WH01-A01-01-01', (string) $code);
    }
}
