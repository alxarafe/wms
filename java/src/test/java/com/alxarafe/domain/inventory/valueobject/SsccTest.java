package com.alxarafe.domain.inventory.valueobject;

import com.alxarafe.app.domain.inventory.valueobject.Sscc;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class SsccTest {

    @Test
    void create() {
        var sscc = new Sscc("123456789012345675");
        assertEquals("123456789012345675", sscc.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new Sscc(null));
    }

    @Test
    void invalidLength() {
        assertThrows(IllegalArgumentException.class, () -> new Sscc("12345678901234567"));
    }

    @Test
    void nonDigits() {
        assertThrows(IllegalArgumentException.class, () -> new Sscc("A23456789012345678"));
    }

    @Test
    void invalidCheckDigit() {
        assertThrows(IllegalArgumentException.class, () -> new Sscc("123456789012345670"));
    }

    @Test
    void testToString() {
        var sscc = new Sscc("123456789012345675");
        assertEquals("123456789012345675", sscc.toString());
    }

    @Test
    void generate() {
        var first = Sscc.generate();
        new Sscc(first.value());
        assertNotEquals(first, Sscc.generate());
    }
}
