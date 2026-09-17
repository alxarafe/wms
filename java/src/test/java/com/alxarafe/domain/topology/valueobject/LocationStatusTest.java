package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.LocationStatus;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class LocationStatusTest {

    @Test
    void values() {
        assertEquals(LocationStatus.ACTIVE, LocationStatus.valueOf("ACTIVE"));
        assertEquals(LocationStatus.BLOCKED, LocationStatus.valueOf("BLOCKED"));
        assertEquals(LocationStatus.DISABLED, LocationStatus.valueOf("DISABLED"));
        assertEquals(3, LocationStatus.values().length);
    }
}
