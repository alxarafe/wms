package com.alxarafe.domain.topology.entity;

import com.alxarafe.app.domain.topology.entity.Zone;
import com.alxarafe.app.domain.topology.valueobject.NamingPolicy;
import com.alxarafe.app.domain.topology.valueobject.WarehouseId;
import com.alxarafe.app.domain.topology.valueobject.ZoneCode;
import com.alxarafe.app.domain.topology.valueobject.ZoneId;
import com.alxarafe.app.domain.topology.valueobject.ZoneTypeId;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ZoneTest {

    @Test
    void create() {
        var id = new ZoneId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var warehouseId = new WarehouseId("018e4e3a-3e7b-7b3e-8000-000000000002");
        var zoneTypeId = new ZoneTypeId("018e4e3a-3e7b-7b3e-8000-000000000003");
        var code = new ZoneCode("PICKING");
        var policy = new NamingPolicy("-", 2, 3, 2, 3, 2);
        var zone = new Zone(id, warehouseId, zoneTypeId, code, policy);

        assertEquals(id, zone.id());
        assertEquals(warehouseId, zone.warehouseId());
        assertEquals(zoneTypeId, zone.zoneTypeId());
        assertEquals(code, zone.code());
        assertEquals(policy, zone.namingPolicy());
    }
}
