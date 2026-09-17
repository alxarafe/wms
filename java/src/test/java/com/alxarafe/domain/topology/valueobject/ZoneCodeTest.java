package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.ZoneCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ZoneCodeTest {

    @Test
    void create() {
        var code = new ZoneCode("PICKING");
        assertEquals("PICKING", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new ZoneCode(null));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new ZoneCode(""));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new ZoneCode("A".repeat(21)));
    }
}
