<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\Entity;

use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ItemFamilyTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new ItemFamilyId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $code = new ItemFamilyCode('ELECTRONICS');
        $family = new ItemFamily($id, $code, 'Consumer Electronics');

        self::assertTrue($id->equals($family->id()));
        self::assertTrue($code->equals($family->code()));
        self::assertSame('Consumer Electronics', $family->name());
    }

    public function testEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemFamily(
            new ItemFamilyId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new ItemFamilyCode('ELEC'),
            '',
        );
    }

    public function testDuplicateAttributeCodes(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemFamily(ItemFamilyId::generate(), new ItemFamilyCode('FOOD'), 'Food', [
            new StorageAttributeCode('FOOD'),
            new StorageAttributeCode('FOOD'),
        ]);
    }
}
