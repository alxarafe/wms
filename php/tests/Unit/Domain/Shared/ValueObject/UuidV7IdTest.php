<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Shared\ValueObject;

use Alxarafe\App\Domain\Shared\ValueObject\UuidV7Id;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class UuidV7IdTest extends TestCase
{
    public function testValidUuidV7(): void
    {
        $uuid = '018e4e3a-3e7b-7b3e-8000-000000000001';
        $id = new TestUuidV7Id($uuid);
        self::assertSame($uuid, $id->value());
    }

    public function testEmptyString(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TestUuidV7Id('');
    }

    public function testInvalidUuidFormat(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TestUuidV7Id('invalid-uuid');
    }

    public function testNonV7Uuid(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new TestUuidV7Id('018e4e3a-3e7b-4b3e-8000-000000000001');
    }

    public function testEquals(): void
    {
        $uuid = '018e4e3a-3e7b-7b3e-8000-000000000001';
        $a = new TestUuidV7Id($uuid);
        $b = new TestUuidV7Id($uuid);
        self::assertTrue($a->equals($b));
    }

    public function testEqualsWithDifferentClass(): void
    {
        $uuid = '018e4e3a-3e7b-7b3e-8000-000000000001';
        $a = new TestUuidV7Id($uuid);
        $b = new OtherTestUuidV7Id($uuid);
        self::assertFalse($a->equals($b));
    }

    public function testEqualsWithDifferentValue(): void
    {
        $a = new TestUuidV7Id('018e4e3a-3e7b-7b3e-8000-000000000001');
        $b = new TestUuidV7Id('018e4e3a-3e7b-7b3e-8000-000000000002');
        self::assertFalse($a->equals($b));
    }

    public function testToString(): void
    {
        $uuid = '018e4e3a-3e7b-7b3e-8000-000000000001';
        $id = new TestUuidV7Id($uuid);
        self::assertSame($uuid, (string) $id);
    }
}

final readonly class TestUuidV7Id extends UuidV7Id {}
final readonly class OtherTestUuidV7Id extends UuidV7Id {}
