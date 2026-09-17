package com.alxarafe.domain.catalogue.valueobject;

import com.alxarafe.app.domain.catalogue.valueobject.UomCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class UomCodeTest {

    @Test
    void create() {
        var code = new UomCode("EA");
        assertEquals("EA", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new UomCode(null));
    }

    @Test
    void empty() {
        assertThrows(IllegalArgumentException.class, () -> new UomCode(""));
    }

    @Test
    void lowercase() {
        assertThrows(IllegalArgumentException.class, () -> new UomCode("ea"));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new UomCode("A".repeat(11)));
    }

    @Test
    void withNumbers() {
        var code = new UomCode("BOX10");
        assertEquals("BOX10", code.value());
    }
}
