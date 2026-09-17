package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.ZoneTypeCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ZoneTypeCodeTest {

    @Test
    void create() {
        var code = new ZoneTypeCode("PICKING");
        assertEquals("PICKING", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new ZoneTypeCode(null));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new ZoneTypeCode(""));
    }

    @Test
    void tooShort() {
        assertThrows(IllegalArgumentException.class, () -> new ZoneTypeCode("A"));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new ZoneTypeCode("A".repeat(21)));
    }

    @Test
    void lowercase() {
        assertThrows(IllegalArgumentException.class, () -> new ZoneTypeCode("picking"));
    }

    @Test
    void withUnderscore() {
        var code = new ZoneTypeCode("BULK_STORAGE");
        assertEquals("BULK_STORAGE", code.value());
    }
}
