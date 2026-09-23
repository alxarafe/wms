<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\Entity;

use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class StorageAttributeTest extends TestCase
{
    public function testNormalizationAndNullableGroup(): void
    {
        $id = new StorageAttributeId('01900000-0000-7000-8000-00000000ABCD');
        $attribute = new StorageAttribute($id, new StorageAttributeCode(' chilled '), ' Refrigerado ', ' thermal ');
        self::assertSame('01900000-0000-7000-8000-00000000abcd', $attribute->id()->value());
        self::assertSame('CHILLED', $attribute->code()->value());
        self::assertSame('Refrigerado', $attribute->name());
        self::assertSame('THERMAL', $attribute->exclusiveGroupCode());
        self::assertNull((new StorageAttribute($id, new StorageAttributeCode('FOOD'), 'Alimento'))->exclusiveGroupCode());
    }

    public function testUnicodeNameAndMaximumCodeLengths(): void
    {
        $attribute = new StorageAttribute(
            new StorageAttributeId('01900000-0000-7000-8000-000000000001'),
            new StorageAttributeCode(str_repeat('a', 30)),
            str_repeat('ñ', 255),
            str_repeat('b', 30),
        );
        self::assertSame(str_repeat('A', 30), $attribute->code()->value());
        self::assertSame(str_repeat('ñ', 255), $attribute->name());
        self::assertSame(str_repeat('B', 30), $attribute->exclusiveGroupCode());
    }

    #[DataProvider('invalidCodes')]
    public function testInvalidCode(string $code): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StorageAttributeCode($code);
    }

    /** @return iterable<string, array{string}> */
    public static function invalidCodes(): iterable
    {
        foreach (['', '  ', '1FOOD', 'HAS FOOD', str_repeat('A', 31), 'IS_FOOD', 'is_chemical', 'IS_CHILLED', 'IS_FROZEN'] as $index => $code) {
            yield (string) $index => [$code];
        }
    }

    #[DataProvider('invalidNames')]
    public function testInvalidName(string $name): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StorageAttribute(new StorageAttributeId('01900000-0000-7000-8000-000000000001'), new StorageAttributeCode('FOOD'), $name);
    }

    /** @return iterable<array{string}> */
    public static function invalidNames(): iterable
    {
        yield [''];
        yield [" \t\n"];
        yield ["Food\0"];
        yield [str_repeat('ñ', 256)];
    }

    #[DataProvider('invalidGroups')]
    public function testInvalidGroup(string $group): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StorageAttribute(new StorageAttributeId('01900000-0000-7000-8000-000000000001'), new StorageAttributeCode('FOOD'), 'Food', $group);
    }

    /** @return iterable<array{string}> */
    public static function invalidGroups(): iterable
    {
        yield [''];
        yield ['  '];
        yield [str_repeat('A', 31)];
        yield ['NOT A CODE'];
    }

    public function testInvalidId(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new StorageAttributeId('FOOD');
    }
}
