<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Catalogue;

use Alxarafe\App\Application\Catalogue\CreateStorageAttribute;
use Alxarafe\App\Application\Catalogue\ReadStorageAttributes;
use Alxarafe\App\Application\Catalogue\StorageAttributeConflict;
use Alxarafe\App\Application\Catalogue\StorageAttributeNotFound;
use Alxarafe\App\Application\Catalogue\StorageAttributeRepository;
use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CreateStorageAttributeTest extends TestCase
{
    public function testCreatesNormalizedAttributeWithUuidV7(): void
    {
        $repository = $this->createMock(StorageAttributeRepository::class);
        $repository->expects(self::once())->method('findByCode')->with(new StorageAttributeCode('FOOD'))->willReturn(null);
        $repository->expects(self::once())->method('save')->with(self::callback(function (StorageAttribute $attribute): bool {
            self::assertSame('FOOD', $attribute->code()->value());
            self::assertSame('Alimento', $attribute->name());
            self::assertNull($attribute->exclusiveGroupCode());
            return true;
        }));
        $attribute = (new CreateStorageAttribute($repository))->execute(' food ', 'Alimento');
        self::assertMatchesRegularExpression('/^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$/', $attribute->id()->value());
    }

    public function testDuplicateDoesNotSave(): void
    {
        $repository = $this->createMock(StorageAttributeRepository::class);
        $repository->expects(self::once())->method('findByCode')->willReturn($this->attribute());
        $repository->expects(self::never())->method('save');
        $this->expectException(StorageAttributeConflict::class);
        (new CreateStorageAttribute($repository))->execute('food', 'Duplicado');
    }

    public function testConcurrentDuplicateFromPortIsPreserved(): void
    {
        $repository = $this->createMock(StorageAttributeRepository::class);
        $repository->expects(self::once())->method('findByCode')->willReturn(null);
        $repository->expects(self::once())->method('save')->willThrowException(new StorageAttributeConflict('Duplicate'));
        $this->expectException(StorageAttributeConflict::class);
        (new CreateStorageAttribute($repository))->execute('FOOD', 'Alimento');
    }

    public function testInvalidInputDoesNotUsePersistence(): void
    {
        $repository = $this->createMock(StorageAttributeRepository::class);
        $repository->expects(self::never())->method('findByCode');
        $repository->expects(self::never())->method('save');
        $this->expectException(InvalidArgumentException::class);
        (new CreateStorageAttribute($repository))->execute('IS_FOOD', 'Alimento');
    }

    public function testReadReturnsExistingRecord(): void
    {
        $attribute = $this->attribute();
        $repository = $this->createMock(StorageAttributeRepository::class);
        $repository->expects(self::once())->method('find')->with($attribute->id())->willReturn($attribute);
        self::assertSame($attribute, (new ReadStorageAttributes($repository))->byId($attribute->id()->value()));
    }

    public function testMissingRecord(): void
    {
        $repository = $this->createMock(StorageAttributeRepository::class);
        $repository->expects(self::once())->method('find')->willReturn(null);
        $this->expectException(StorageAttributeNotFound::class);
        (new ReadStorageAttributes($repository))->byId($this->attribute()->id()->value());
    }

    private function attribute(): StorageAttribute
    {
        return new StorageAttribute(new StorageAttributeId('01900000-0000-7000-8000-000000000001'), new StorageAttributeCode('FOOD'), 'Alimento');
    }
}
