package com.alxarafe.domain.topology.entity;

import com.alxarafe.app.domain.topology.entity.Location;
import com.alxarafe.app.domain.topology.valueobject.AisleId;
import com.alxarafe.app.domain.topology.valueobject.LocationCode;
import com.alxarafe.app.domain.topology.valueobject.LocationId;
import com.alxarafe.app.domain.topology.valueobject.LocationRole;
import com.alxarafe.app.domain.topology.valueobject.LocationStatus;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class LocationTest {

    private LocationId id;
    private AisleId aisleId;
    private LocationCode code;

    @BeforeEach
    void setUp() {
        id = new LocationId("018e4e3a-3e7b-7b3e-8000-000000000001");
        aisleId = new AisleId("018e4e3a-3e7b-7b3e-8000-000000000002");
        code = new LocationCode("WH01-A01-01-01");
    }

    @Test
    void create() {
        var location = new Location(id, aisleId, 1, 1, code, LocationRole.PICKING, LocationStatus.ACTIVE);
        assertEquals(id, location.id());
        assertEquals(aisleId, location.aisleId());
        assertEquals(1, location.bay());
        assertEquals(1, location.level());
        assertEquals(code, location.code());
        assertEquals(LocationRole.PICKING, location.role());
        assertEquals(LocationStatus.ACTIVE, location.status());
    }

    @Test
    void invalidBay() {
        assertThrows(IllegalArgumentException.class,
                () -> new Location(id, aisleId, 0, 1, code, LocationRole.PICKING, LocationStatus.ACTIVE));
    }

    @Test
    void invalidLevel() {
        assertThrows(IllegalArgumentException.class,
                () -> new Location(id, aisleId, 1, 0, code, LocationRole.PICKING, LocationStatus.ACTIVE));
    }

    @Test
    void isActive() {
        var location = new Location(id, aisleId, 1, 1, code, LocationRole.PICKING, LocationStatus.ACTIVE);
        assertTrue(location.isActive());
    }

    @Test
    void block() {
        var location = new Location(id, aisleId, 1, 1, code, LocationRole.PICKING, LocationStatus.ACTIVE);
        location.block();
        assertEquals(LocationStatus.BLOCKED, location.status());
        assertFalse(location.isActive());
    }

    @Test
    void activate() {
        var location = new Location(id, aisleId, 1, 1, code, LocationRole.PICKING, LocationStatus.BLOCKED);
        location.activate();
        assertEquals(LocationStatus.ACTIVE, location.status());
    }

    @Test
    void disable() {
        var location = new Location(id, aisleId, 1, 1, code, LocationRole.PICKING, LocationStatus.ACTIVE);
        location.disable();
        assertEquals(LocationStatus.DISABLED, location.status());
    }

    @Test
    void updateCode() {
        var location = new Location(id, aisleId, 1, 1, code, LocationRole.PICKING, LocationStatus.ACTIVE);
        var newCode = new LocationCode("NEW-CODE");
        location.updateCode(newCode);
        assertEquals(newCode, location.code());
    }
}
