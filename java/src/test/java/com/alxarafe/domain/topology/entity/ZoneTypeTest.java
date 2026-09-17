package com.alxarafe.domain.topology.entity;

import com.alxarafe.app.domain.topology.entity.ZoneType;
import com.alxarafe.app.domain.topology.valueobject.ZoneTypeCode;
import com.alxarafe.app.domain.topology.valueobject.ZoneTypeId;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ZoneTypeTest {

    @Test
    void create() {
        var id = new ZoneTypeId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var code = new ZoneTypeCode("PICKING");
        var zoneType = new ZoneType(id, code, true, false);

        assertEquals(id, zoneType.id());
        assertEquals(code, zoneType.code());
        assertTrue(zoneType.isOperative());
        assertFalse(zoneType.allowsMultiSku());
    }
}
