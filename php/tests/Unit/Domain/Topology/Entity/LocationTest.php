<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\Entity;

use Alxarafe\App\Domain\Topology\Entity\Location;
use Alxarafe\App\Domain\Topology\ValueObject\AisleId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationCode;
use Alxarafe\App\Domain\Topology\ValueObject\LocationId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationRole;
use Alxarafe\App\Domain\Topology\ValueObject\LocationStatus;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class LocationTest extends TestCase
{
    private LocationId $id;
    private AisleId $aisleId;
    private LocationCode $code;

    protected function setUp(): void
    {
        $this->id = new LocationId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $this->aisleId = new AisleId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $this->code = new LocationCode('WH01-A01-01-01');
    }

    public function testCreate(): void
    {
        $location = new Location($this->id, $this->aisleId, 1, 1, $this->code, LocationRole::PICKING, LocationStatus::ACTIVE);
        self::assertTrue($this->id->equals($location->id()));
        self::assertTrue($this->aisleId->equals($location->aisleId()));
        self::assertSame(1, $location->bay());
        self::assertSame(1, $location->level());
        self::assertTrue($this->code->equals($location->code()));
        self::assertSame(LocationRole::PICKING, $location->role());
        self::assertSame(LocationStatus::ACTIVE, $location->status());
    }

    public function testInvalidBay(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Location($this->id, $this->aisleId, 0, 1, $this->code, LocationRole::PICKING, LocationStatus::ACTIVE);
    }

    public function testInvalidLevel(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new Location($this->id, $this->aisleId, 1, 0, $this->code, LocationRole::PICKING, LocationStatus::ACTIVE);
    }

    public function testIsActive(): void
    {
        $location = new Location($this->id, $this->aisleId, 1, 1, $this->code, LocationRole::PICKING, LocationStatus::ACTIVE);
        self::assertTrue($location->isActive());
    }

    public function testBlock(): void
    {
        $location = new Location($this->id, $this->aisleId, 1, 1, $this->code, LocationRole::PICKING, LocationStatus::ACTIVE);
        $location->block();
        self::assertSame(LocationStatus::BLOCKED, $location->status());
        self::assertFalse($location->isActive());
    }

    public function testActivate(): void
    {
        $location = new Location($this->id, $this->aisleId, 1, 1, $this->code, LocationRole::PICKING, LocationStatus::BLOCKED);
        $location->activate();
        self::assertSame(LocationStatus::ACTIVE, $location->status());
    }

    public function testDisable(): void
    {
        $location = new Location($this->id, $this->aisleId, 1, 1, $this->code, LocationRole::PICKING, LocationStatus::ACTIVE);
        $location->disable();
        self::assertSame(LocationStatus::DISABLED, $location->status());
    }

    public function testUpdateCode(): void
    {
        $location = new Location($this->id, $this->aisleId, 1, 1, $this->code, LocationRole::PICKING, LocationStatus::ACTIVE);
        $newCode = new LocationCode('NEW-CODE');
        $location->updateCode($newCode);
        self::assertTrue($newCode->equals($location->code()));
    }
}
