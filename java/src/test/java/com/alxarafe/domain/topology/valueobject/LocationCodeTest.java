package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.LocationCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class LocationCodeTest {

    @Test
    void create() {
        var code = new LocationCode("WH01-A01-01-01");
        assertEquals("WH01-A01-01-01", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new LocationCode(null));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new LocationCode(""));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new LocationCode("A".repeat(51)));
    }
}
