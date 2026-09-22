<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\Entity;

use Alxarafe\App\Domain\Catalogue\Entity\Item;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemUomConversion;
use Alxarafe\App\Domain\Catalogue\ValueObject\Sku;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ItemTest extends TestCase
{
    private ItemId $id;
    private Sku $sku;
    private ItemFamilyId $familyId;
    private UomId $baseUomId;

    protected function setUp(): void
    {
        $this->id = new ItemId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $this->sku = new Sku('WIDGET-001');
        $this->familyId = new ItemFamilyId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $this->baseUomId = new UomId('018e4e3a-3e7b-7b3e-8000-000000000003');
    }

    public function testCreate(): void
    {
        $item = new Item($this->id, $this->sku, 'Widget', $this->familyId, $this->baseUomId, false, false);
        self::assertTrue($this->id->equals($item->id()));
        self::assertTrue($this->sku->equals($item->sku()));
        self::assertSame('Widget', $item->name());
        self::assertTrue($this->familyId->equals($item->familyId()));
        self::assertTrue($this->baseUomId->equals($item->baseUomId()));
        self::assertFalse($item->isBatchManaged());
        self::assertFalse($item->isExpirable());
    }

    public function testEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Item($this->id, $this->sku, '', $this->familyId, $this->baseUomId, false, false);
    }

    public function testExpirableRequiresBatchManaged(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Item($this->id, $this->sku, 'Cheese', $this->familyId, $this->baseUomId, false, true);
    }

    public function testExpirableWithBatchManaged(): void
    {
        $item = new Item($this->id, $this->sku, 'Cheese', $this->familyId, $this->baseUomId, true, true);
        self::assertTrue($item->isBatchManaged());
        self::assertTrue($item->isExpirable());
    }

    public function testAddUomConversion(): void
    {
        $item = new Item($this->id, $this->sku, 'Widget', $this->familyId, $this->baseUomId, false, false);
        $conversion = new ItemUomConversion(
            $this->baseUomId,
            new UomId('018e4e3a-3e7b-7b3e-8000-000000000004'),
            12.0,
        );
        $item->addUomConversion($conversion);
        self::assertCount(1, $item->uomConversions());
    }

    public function testAddDuplicateUomConversion(): void
    {
        $item = new Item($this->id, $this->sku, 'Widget', $this->familyId, $this->baseUomId, false, false);
        $toUomId = new UomId('018e4e3a-3e7b-7b3e-8000-000000000004');
        $conversion = new ItemUomConversion($this->baseUomId, $toUomId, 12.0);
        $item->addUomConversion($conversion);

        $this->expectException(InvalidArgumentException::class);
        $item->addUomConversion($conversion);
    }
}
