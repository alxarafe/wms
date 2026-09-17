package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.LocationRole;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class LocationRoleTest {

    @Test
    void values() {
        assertEquals(LocationRole.PICKING, LocationRole.valueOf("PICKING"));
        assertEquals(LocationRole.RESERVE, LocationRole.valueOf("RESERVE"));
        assertEquals(2, LocationRole.values().length);
    }
}
