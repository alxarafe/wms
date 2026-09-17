<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Inventory\Entity\StockQuant;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchId;
use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use Alxarafe\App\Domain\Inventory\ValueObject\StockQuantId;
use PHPUnit\Framework\TestCase;

final class StockQuantTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $huId = new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $itemId = new ItemId('018e4e3a-3e7b-7b3e-8000-000000000003');
        $qty = new Quantity(10.0, 'EA');
        $quant = new StockQuant($id, $huId, $itemId, null, $qty);

        self::assertTrue($id->equals($quant->id()));
        self::assertTrue($huId->equals($quant->huId()));
        self::assertTrue($itemId->equals($quant->itemId()));
        self::assertNull($quant->batchId());
        self::assertTrue($qty->equals($quant->quantity()));
    }

    public function testCreateWithBatch(): void
    {
        $batchId = new BatchId('018e4e3a-3e7b-7b3e-8000-000000000004');
        $quant = new StockQuant(
            new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new ItemId('018e4e3a-3e7b-7b3e-8000-000000000003'),
            $batchId,
            new Quantity(5.0, 'EA'),
        );
        self::assertTrue($batchId->equals($quant->batchId()));
    }

    public function testAdd(): void
    {
        $quant = new StockQuant(
            new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new ItemId('018e4e3a-3e7b-7b3e-8000-000000000003'),
            null,
            new Quantity(10.0, 'EA'),
        );
        $quant->add(new Quantity(5.0, 'EA'));
        self::assertTrue((new Quantity(15.0, 'EA'))->equals($quant->quantity()));
    }

    public function testSubtract(): void
    {
        $quant = new StockQuant(
            new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new ItemId('018e4e3a-3e7b-7b3e-8000-000000000003'),
            null,
            new Quantity(10.0, 'EA'),
        );
        $quant->subtract(new Quantity(3.0, 'EA'));
        self::assertTrue((new Quantity(7.0, 'EA'))->equals($quant->quantity()));
    }
}
