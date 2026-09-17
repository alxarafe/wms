<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Inventory\Entity\HandlingUnit;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchId;
use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Inventory\ValueObject\HuStatus;
use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use Alxarafe\App\Domain\Inventory\ValueObject\Sscc;
use Alxarafe\App\Domain\Inventory\ValueObject\StockQuantId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class HandlingUnitTest extends TestCase
{
    private HandlingUnitId $id;
    private Sscc $code;
    private HandlingUnitId $parentId;

    protected function setUp(): void
    {
        $this->id = new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $this->code = new Sscc('123456789012345675');
        $this->parentId = new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002');
    }

    public function testCreate(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        self::assertTrue($this->id->equals($hu->id()));
        self::assertTrue($this->code->equals($hu->code()));
        self::assertNull($hu->locationId());
        self::assertNull($hu->parentHuId());
        self::assertSame(HuStatus::AVAILABLE, $hu->status());
    }

    public function testCannotBeOwnParent(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new HandlingUnit($this->id, $this->code, null, $this->id, HuStatus::AVAILABLE);
    }

    public function testIsAvailable(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        self::assertTrue($hu->isAvailable());

        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::BLOCKED);
        self::assertFalse($hu->isAvailable());
    }

    public function testMoveToLocation(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        $locId = new LocationId('018e4e3a-3e7b-7b3e-8000-000000000010');
        $hu->moveToLocation($locId);

        self::assertTrue($locId->equals($hu->locationId()));
        self::assertNull($hu->parentHuId());
    }

    public function testNestIntoParent(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        $hu->nestIntoParent($this->parentId);

        self::assertTrue($this->parentId->equals($hu->parentHuId()));
        self::assertNull($hu->locationId());
    }

    public function testNestIntoSelf(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        $this->expectException(InvalidArgumentException::class);
        $hu->nestIntoParent($this->id);
    }

    public function testReleaseFromParent(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, $this->parentId, HuStatus::AVAILABLE);
        $locId = new LocationId('018e4e3a-3e7b-7b3e-8000-000000000010');
        $hu->releaseFromParent($locId);

        self::assertNull($hu->parentHuId());
        self::assertTrue($locId->equals($hu->locationId()));
    }

    public function testUpdateStatus(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        $hu->updateStatus(HuStatus::BLOCKED);
        self::assertSame(HuStatus::BLOCKED, $hu->status());
    }

    public function testAddQuant(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        $itemId = new ItemId('018e4e3a-3e7b-7b3e-8000-000000000020');
        $quantId = new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000030');
        $hu->addQuant($quantId, $itemId, null, new Quantity(10.0, 'EA'));

        self::assertCount(1, $hu->quants());
    }

    public function testAddQuantMergesSameItemBatch(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        $itemId = new ItemId('018e4e3a-3e7b-7b3e-8000-000000000020');
        $quantId1 = new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000030');
        $quantId2 = new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000031');

        $hu->addQuant($quantId1, $itemId, null, new Quantity(10.0, 'EA'));
        $hu->addQuant($quantId2, $itemId, null, new Quantity(5.0, 'EA'));

        self::assertCount(1, $hu->quants());
        self::assertTrue((new Quantity(15.0, 'EA'))->equals($hu->quants()[0]->quantity()));
    }

    public function testAddQuantDifferentBatch(): void
    {
        $hu = new HandlingUnit($this->id, $this->code, null, null, HuStatus::AVAILABLE);
        $itemId = new ItemId('018e4e3a-3e7b-7b3e-8000-000000000020');
        $batch1 = new BatchId('018e4e3a-3e7b-7b3e-8000-000000000040');
        $batch2 = new BatchId('018e4e3a-3e7b-7b3e-8000-000000000041');

        $hu->addQuant(new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000030'), $itemId, $batch1, new Quantity(10.0, 'EA'));
        $hu->addQuant(new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000031'), $itemId, $batch2, new Quantity(5.0, 'EA'));

        self::assertCount(2, $hu->quants());
    }
}
