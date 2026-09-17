<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory\ValueObject;

use Alxarafe\App\Domain\Inventory\ValueObject\Sscc;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class SsccTest extends TestCase
{
    public function testCreate(): void
    {
        $sscc = new Sscc('123456789012345675');
        self::assertSame('123456789012345675', $sscc->value());
    }

    public function testInvalidLength(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sscc('12345678901234567');
    }

    public function testNonDigits(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sscc('A23456789012345678');
    }

    public function testInvalidCheckDigit(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Sscc('123456789012345670');
    }

    public function testValidCheckDigit(): void
    {
        $sscc = new Sscc('123456789012345675');
        self::assertSame('123456789012345675', $sscc->value());
    }

    public function testEquals(): void
    {
        $a = new Sscc('123456789012345675');
        $b = new Sscc('123456789012345675');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $sscc = new Sscc('123456789012345675');
        self::assertSame('123456789012345675', (string) $sscc);
    }
}
