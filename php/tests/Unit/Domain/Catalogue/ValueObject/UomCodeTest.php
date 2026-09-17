<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Catalogue\ValueObject\UomCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UomCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new UomCode('EA');
        self::assertSame('EA', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UomCode('');
    }

    public function testLowercase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UomCode('ea');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new UomCode(str_repeat('A', 11));
    }

    public function testWithNumbers(): void
    {
        $code = new UomCode('BOX10');
        self::assertSame('BOX10', $code->value());
    }

    public function testEquals(): void
    {
        $a = new UomCode('PAL');
        $b = new UomCode('PAL');
        $c = new UomCode('EA');
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }

    public function testToString(): void
    {
        $code = new UomCode('KG');
        self::assertSame('KG', (string) $code);
    }
}
