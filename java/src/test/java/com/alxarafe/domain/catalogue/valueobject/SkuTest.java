package com.alxarafe.domain.catalogue.valueobject;

import com.alxarafe.app.domain.catalogue.valueobject.Sku;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class SkuTest {

    @Test
    void create() {
        var sku = new Sku("WIDGET-001");
        assertEquals("WIDGET-001", sku.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new Sku(null));
    }

    @Test
    void empty() {
        assertThrows(IllegalArgumentException.class, () -> new Sku(""));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new Sku("   "));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new Sku("A".repeat(51)));
    }

    @Test
    void testToString() {
        var sku = new Sku("TEST");
        assertEquals("TEST", sku.toString());
    }
}
