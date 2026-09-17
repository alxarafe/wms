package com.alxarafe.domain.catalogue.valueobject;

import com.alxarafe.app.domain.catalogue.valueobject.ItemUomConversion;
import com.alxarafe.app.domain.catalogue.valueobject.UomId;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ItemUomConversionTest {

    private final UomId fromUomId = new UomId("018e4e3a-3e7b-7b3e-8000-000000000001");
    private final UomId toUomId = new UomId("018e4e3a-3e7b-7b3e-8000-000000000002");

    @Test
    void createConversion() {
        var conversion = new ItemUomConversion(fromUomId, toUomId, 2.0);
        assertEquals(fromUomId, conversion.fromUomId());
        assertEquals(toUomId, conversion.toUomId());
        assertEquals(2.0, conversion.factor());
    }

    @Test
    void negativeFactor() {
        assertThrows(IllegalArgumentException.class,
                () -> new ItemUomConversion(fromUomId, toUomId, -1.0));
    }

    @Test
    void zeroFactor() {
        assertThrows(IllegalArgumentException.class,
                () -> new ItemUomConversion(fromUomId, toUomId, 0.0));
    }

    @Test
    void sameUom() {
        assertThrows(IllegalArgumentException.class,
                () -> new ItemUomConversion(fromUomId, fromUomId, 1.0));
    }

    @Test
    void nullFromUom() {
        assertThrows(NullPointerException.class,
                () -> new ItemUomConversion(null, toUomId, 1.0));
    }
}
