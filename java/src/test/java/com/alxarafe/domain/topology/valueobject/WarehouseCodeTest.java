package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.WarehouseCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class WarehouseCodeTest {

    @Test
    void create() {
        var code = new WarehouseCode("WH01");
        assertEquals("WH01", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new WarehouseCode(null));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new WarehouseCode(""));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new WarehouseCode("A".repeat(11)));
    }
}
