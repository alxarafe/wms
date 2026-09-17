<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\Entity\ZoneType;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneTypeCode;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneTypeId;
use PHPUnit\Framework\TestCase;

final class ZoneTypeTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new ZoneTypeId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $code = new ZoneTypeCode('PICKING');
        $zoneType = new ZoneType($id, $code, true, false);

        self::assertTrue($id->equals($zoneType->id()));
        self::assertTrue($code->equals($zoneType->code()));
        self::assertTrue($zoneType->isOperative());
        self::assertFalse($zoneType->allowsMultiSku());
    }
}
