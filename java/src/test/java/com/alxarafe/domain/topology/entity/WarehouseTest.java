package com.alxarafe.domain.topology.entity;

import com.alxarafe.app.domain.topology.entity.Warehouse;
import com.alxarafe.app.domain.topology.valueobject.WarehouseCode;
import com.alxarafe.app.domain.topology.valueobject.WarehouseId;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class WarehouseTest {

    @Test
    void create() {
        var id = new WarehouseId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var code = new WarehouseCode("WH01");
        var warehouse = new Warehouse(id, code, "Main Warehouse");

        assertEquals(id, warehouse.id());
        assertEquals(code, warehouse.code());
        assertEquals("Main Warehouse", warehouse.name());
    }

    @Test
    void nullName() {
        assertThrows(NullPointerException.class, () -> new Warehouse(
                new WarehouseId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new WarehouseCode("WH01"),
                null));
    }

    @Test
    void blankName() {
        assertThrows(IllegalArgumentException.class, () -> new Warehouse(
                new WarehouseId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new WarehouseCode("WH01"),
                ""));
    }
}
