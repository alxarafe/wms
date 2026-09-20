package com.alxarafe.domain.rules.event;

import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.rules.event.StockMoved;
import com.alxarafe.app.domain.rules.valueobject.MovementType;
import com.alxarafe.app.domain.rules.valueobject.StockMovementId;
import com.alxarafe.app.domain.topology.valueobject.LocationId;
import org.junit.jupiter.api.Test;

import java.time.Instant;

import static org.junit.jupiter.api.Assertions.*;

class StockMovedTest {

    @Test
    void create() {
        var id = new StockMovementId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var huId = new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002");
        var from = new LocationId("018e4e3a-3e7b-7b3e-8000-000000000003");
        var to = new LocationId("018e4e3a-3e7b-7b3e-8000-000000000004");
        var now = Instant.now();

        var event = new StockMoved(id, MovementType.TRANSFER, huId, from, to, now);

        assertEquals(id, event.id());
        assertEquals(MovementType.TRANSFER, event.type());
        assertEquals(huId, event.huId());
        assertEquals(from, event.fromLocationId().orElseThrow());
        assertEquals(to, event.toLocationId().orElseThrow());
        assertEquals(now, event.performedAt());
    }

    @Test
    void nullFromLocation() {
        var event = new StockMoved(
                new StockMovementId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                MovementType.INBOUND,
                new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                null,
                new LocationId("018e4e3a-3e7b-7b3e-8000-000000000004"),
                Instant.now());
        assertTrue(event.fromLocationId().isEmpty());
    }

    @Test
    void nullToLocationForOutbound() {
        var event = new StockMoved(
                new StockMovementId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                MovementType.OUTBOUND,
                new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new LocationId("018e4e3a-3e7b-7b3e-8000-000000000003"),
                null,
                Instant.now());
        assertEquals("018e4e3a-3e7b-7b3e-8000-000000000003",
                event.fromLocationId().orElseThrow().value());
        assertTrue(event.toLocationId().isEmpty());
    }
}
