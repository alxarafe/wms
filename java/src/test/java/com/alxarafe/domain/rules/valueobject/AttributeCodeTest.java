package com.alxarafe.domain.rules.valueobject;

import com.alxarafe.app.domain.rules.valueobject.AttributeCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class AttributeCodeTest {

    @Test
    void create() {
        var code = new AttributeCode("COLD");
        assertEquals("COLD", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new AttributeCode(null));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new AttributeCode(""));
    }

    @Test
    void tooShort() {
        assertThrows(IllegalArgumentException.class, () -> new AttributeCode("A"));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new AttributeCode("A".repeat(21)));
    }

    @Test
    void lowercase() {
        assertThrows(IllegalArgumentException.class, () -> new AttributeCode("cold"));
    }

    @Test
    void withUnderscore() {
        var code = new AttributeCode("HAZMAT_CLASS");
        assertEquals("HAZMAT_CLASS", code.value());
    }
}
