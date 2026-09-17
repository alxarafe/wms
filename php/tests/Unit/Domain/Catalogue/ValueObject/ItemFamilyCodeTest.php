<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ItemFamilyCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new ItemFamilyCode('ELECTRONICS');
        self::assertSame('ELECTRONICS', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemFamilyCode('');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemFamilyCode(str_repeat('A', 21));
    }

    public function testEquals(): void
    {
        $a = new ItemFamilyCode('FOOD');
        $b = new ItemFamilyCode('FOOD');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new ItemFamilyCode('BEV');
        self::assertSame('BEV', (string) $code);
    }
}
