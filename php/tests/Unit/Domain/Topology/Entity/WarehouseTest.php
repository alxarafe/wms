<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\Entity\Warehouse;
use Alxarafe\App\Domain\Topology\ValueObject\WarehouseCode;
use Alxarafe\App\Domain\Topology\ValueObject\WarehouseId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class WarehouseTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new WarehouseId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $code = new WarehouseCode('WH01');
        $warehouse = new Warehouse($id, $code, 'Main Warehouse');

        self::assertTrue($id->equals($warehouse->id()));
        self::assertTrue($code->equals($warehouse->code()));
        self::assertSame('Main Warehouse', $warehouse->name());
    }

    public function testEmptyName(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Warehouse(
            new WarehouseId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new WarehouseCode('WH01'),
            '',
        );
    }
}
