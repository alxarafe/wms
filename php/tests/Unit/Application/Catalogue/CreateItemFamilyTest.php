<?php

declare(strict_types=1);

namespace Tests\Unit\Application\Catalogue;

use Alxarafe\App\Application\Catalogue\CreateItemFamily;
use Alxarafe\App\Application\Catalogue\ItemFamilyConflict;
use Alxarafe\App\Application\Catalogue\ItemFamilyRepository;
use Alxarafe\App\Application\Catalogue\StorageAttributeNotFound;
use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use PHPUnit\Framework\TestCase;

final class CreateItemFamilyTest extends TestCase
{
    private const FIRST = 'FOOD';
    private const SECOND = 'CHILLED';

    public function testValidatesAllReferencesBeforeSingleAtomicSave(): void
    {
        $repository = $this->createMock(ItemFamilyRepository::class);
        $repository->expects(self::once())->method('existingAttributeCodes')
            ->with([new StorageAttributeCode(self::FIRST), new StorageAttributeCode(self::SECOND)])
            ->willReturn([self::FIRST, self::SECOND]);
        $repository->expects(self::once())->method('findByCode')->willReturn(null);
        $repository->expects(self::once())->method('save')->with(self::callback(function (ItemFamily $family): bool {
            self::assertCount(2, $family->attributes());
            self::assertSame('ALIMENTOS', $family->code()->value());
            return true;
        }));
        $family = (new CreateItemFamily($repository))->execute('ALIMENTOS', 'Alimentos', [self::FIRST, self::SECOND]);
        self::assertSame(self::SECOND, $family->attributes()[1]->value());
    }

    public function testMissingLastAttributeDoesNotSaveFamily(): void
    {
        $repository = $this->createMock(ItemFamilyRepository::class);
        $repository->expects(self::once())->method('existingAttributeCodes')->willReturn([self::FIRST]);
        $repository->expects(self::never())->method('findByCode');
        $repository->expects(self::never())->method('save');
        $this->expectException(StorageAttributeNotFound::class);
        (new CreateItemFamily($repository))->execute('BAD', 'Sin persistir', [self::FIRST, self::SECOND]);
    }

    public function testEmptyAttributesRemainValid(): void
    {
        $repository = $this->createMock(ItemFamilyRepository::class);
        $repository->expects(self::once())->method('existingAttributeCodes')->with([])->willReturn([]);
        $repository->expects(self::once())->method('findByCode')->willReturn(null);
        $repository->expects(self::once())->method('save');
        self::assertSame([], (new CreateItemFamily($repository))->execute('NEUTRAL', 'Neutros', [])->attributes());
    }

    public function testDuplicateFamilyDoesNotSave(): void
    {
        $family = new ItemFamily(ItemFamilyId::generate(), new ItemFamilyCode('NEUTRAL'), 'Neutros');
        $repository = $this->createMock(ItemFamilyRepository::class);
        $repository->expects(self::once())->method('existingAttributeCodes')->willReturn([]);
        $repository->expects(self::once())->method('findByCode')->willReturn($family);
        $repository->expects(self::never())->method('save');
        $this->expectException(ItemFamilyConflict::class);
        (new CreateItemFamily($repository))->execute('NEUTRAL', 'Duplicada', []);
    }

    public function testAttributeCodesAreAcceptedAsIdentifiers(): void
    {
        $repository = $this->createMock(ItemFamilyRepository::class);
        $repository->expects(self::once())->method('existingAttributeCodes')->with([new StorageAttributeCode('FOOD')])->willReturn(['FOOD']);
        $repository->expects(self::once())->method('findByCode')->willReturn(null);
        $repository->expects(self::once())->method('save');
        self::assertSame('FOOD', (new CreateItemFamily($repository))->execute('BAD', 'Con código', ['FOOD'])->attributes()[0]->value());
    }

    public function testPersistenceFailureIsNotReportedAsSuccess(): void
    {
        $repository = $this->createMock(ItemFamilyRepository::class);
        $repository->expects(self::once())->method('existingAttributeCodes')->willReturn([self::FIRST]);
        $repository->expects(self::once())->method('findByCode')->willReturn(null);
        $repository->expects(self::once())->method('save')->willThrowException(new StorageAttributeNotFound('Gone'));
        $this->expectException(StorageAttributeNotFound::class);
        (new CreateItemFamily($repository))->execute('BAD', 'Referencia retirada', [self::FIRST]);
    }
}
