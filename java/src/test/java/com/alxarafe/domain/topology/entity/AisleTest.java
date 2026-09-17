package com.alxarafe.domain.topology.entity;

import com.alxarafe.app.domain.topology.entity.Aisle;
import com.alxarafe.app.domain.topology.valueobject.AisleCode;
import com.alxarafe.app.domain.topology.valueobject.AisleId;
import com.alxarafe.app.domain.topology.valueobject.ZoneId;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class AisleTest {

    @Test
    void create() {
        var id = new AisleId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var zoneId = new ZoneId("018e4e3a-3e7b-7b3e-8000-000000000002");
        var code = new AisleCode("A-01");
        var aisle = new Aisle(id, zoneId, code);

        assertEquals(id, aisle.id());
        assertEquals(zoneId, aisle.zoneId());
        assertEquals(code, aisle.code());
        assertFalse(aisle.isBlocked());
    }

    @Test
    void block() {
        var aisle = new Aisle(
                new AisleId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ZoneId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new AisleCode("A-01"));
        aisle.block();
        assertTrue(aisle.isBlocked());
    }

    @Test
    void unblock() {
        var aisle = new Aisle(
                new AisleId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ZoneId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new AisleCode("A-01"));
        aisle.block();
        aisle.unblock();
        assertFalse(aisle.isBlocked());
    }
}
