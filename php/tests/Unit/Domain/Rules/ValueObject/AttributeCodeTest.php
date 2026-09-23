<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\ValueObject;

use Alxarafe\App\Domain\Rules\ValueObject\AttributeCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AttributeCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new AttributeCode('COLD');
        self::assertSame('COLD', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AttributeCode('');
    }

    public function testTooShort(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AttributeCode('A');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AttributeCode(str_repeat('A', 21));
    }

    public function testLowercase(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AttributeCode('cold');
    }

    public function testWithUnderscore(): void
    {
        $code = new AttributeCode('HAZMAT_CLASS');
        self::assertSame('HAZMAT_CLASS', $code->value());
    }

    public function testEquals(): void
    {
        $a = new AttributeCode('CHILLED');
        $b = new AttributeCode('CHILLED');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new AttributeCode('DRY');
        self::assertSame('DRY', (string) $code);
    }
}
