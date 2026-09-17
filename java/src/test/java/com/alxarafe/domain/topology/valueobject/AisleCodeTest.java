package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.AisleCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class AisleCodeTest {

    @Test
    void create() {
        var code = new AisleCode("A-01");
        assertEquals("A-01", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new AisleCode(null));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new AisleCode(""));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new AisleCode("A".repeat(21)));
    }
}
