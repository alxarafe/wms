<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\Entity\Zone;
use Alxarafe\App\Domain\Topology\ValueObject\NamingPolicy;
use Alxarafe\App\Domain\Topology\ValueObject\WarehouseId;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneCode;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneId;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneTypeId;
use PHPUnit\Framework\TestCase;

final class ZoneTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new ZoneId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $warehouseId = new WarehouseId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $zoneTypeId = new ZoneTypeId('018e4e3a-3e7b-7b3e-8000-000000000003');
        $code = new ZoneCode('PICKING');
        $policy = new NamingPolicy('-', 2, 3, 2, 3, 2);
        $zone = new Zone($id, $warehouseId, $zoneTypeId, $code, $policy);

        self::assertTrue($id->equals($zone->id()));
        self::assertTrue($warehouseId->equals($zone->warehouseId()));
        self::assertTrue($zoneTypeId->equals($zone->zoneTypeId()));
        self::assertTrue($code->equals($zone->code()));
        self::assertTrue($policy->equals($zone->namingPolicy()));
    }
}
