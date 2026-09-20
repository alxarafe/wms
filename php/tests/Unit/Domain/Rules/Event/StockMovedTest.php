<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\Event;

use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Rules\Event\StockMoved;
use Alxarafe\App\Domain\Rules\ValueObject\MovementType;
use Alxarafe\App\Domain\Rules\ValueObject\StockMovementId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class StockMovedTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new StockMovementId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $huId = new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $from = new LocationId('018e4e3a-3e7b-7b3e-8000-000000000003');
        $to = new LocationId('018e4e3a-3e7b-7b3e-8000-000000000004');
        $now = new DateTimeImmutable();

        $event = new StockMoved($id, MovementType::TRANSFER, $huId, $from, $to, $now);

        self::assertTrue($id->equals($event->id()));
        self::assertSame(MovementType::TRANSFER, $event->type());
        self::assertTrue($huId->equals($event->huId()));
        self::assertInstanceOf(LocationId::class, $event->fromLocationId());
        self::assertInstanceOf(LocationId::class, $event->toLocationId());
        self::assertTrue($from->equals($event->fromLocationId()));
        self::assertTrue($to->equals($event->toLocationId()));
        self::assertSame($now, $event->performedAt());
    }

    public function testNullFromLocation(): void
    {
        $event = new StockMoved(
            new StockMovementId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            MovementType::INBOUND,
            new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            null,
            new LocationId('018e4e3a-3e7b-7b3e-8000-000000000004'),
            new DateTimeImmutable(),
        );
        self::assertNull($event->fromLocationId());
    }

    public function testNullToLocationForOutbound(): void
    {
        $event = new StockMoved(
            new StockMovementId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            MovementType::OUTBOUND,
            new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new LocationId('018e4e3a-3e7b-7b3e-8000-000000000003'),
            null,
            new DateTimeImmutable(),
        );
        self::assertNotNull($event->fromLocationId());
        self::assertNull($event->toLocationId());
    }
}
