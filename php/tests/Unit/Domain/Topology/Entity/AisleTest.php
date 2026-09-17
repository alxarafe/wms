<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\Entity\Aisle;
use Alxarafe\App\Domain\Topology\ValueObject\AisleCode;
use Alxarafe\App\Domain\Topology\ValueObject\AisleId;
use Alxarafe\App\Domain\Topology\ValueObject\ZoneId;
use PHPUnit\Framework\TestCase;

final class AisleTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new AisleId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $zoneId = new ZoneId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $code = new AisleCode('A-01');
        $aisle = new Aisle($id, $zoneId, $code);

        self::assertTrue($id->equals($aisle->id()));
        self::assertTrue($zoneId->equals($aisle->zoneId()));
        self::assertTrue($code->equals($aisle->code()));
        self::assertFalse($aisle->isBlocked());
    }

    public function testBlock(): void
    {
        $aisle = new Aisle(
            new AisleId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new ZoneId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new AisleCode('A-01'),
        );
        $aisle->block();
        self::assertTrue($aisle->isBlocked());
    }

    public function testUnblock(): void
    {
        $aisle = new Aisle(
            new AisleId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new ZoneId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new AisleCode('A-01'),
        );
        $aisle->block();
        $aisle->unblock();
        self::assertFalse($aisle->isBlocked());
    }
}
