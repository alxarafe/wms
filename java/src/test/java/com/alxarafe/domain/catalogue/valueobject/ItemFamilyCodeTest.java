package com.alxarafe.domain.catalogue.valueobject;

import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ItemFamilyCodeTest {

    @Test
    void create() {
        var code = new ItemFamilyCode("ELECTRONICS");
        assertEquals("ELECTRONICS", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new ItemFamilyCode(null));
    }

    @Test
    void empty() {
        assertThrows(IllegalArgumentException.class, () -> new ItemFamilyCode(""));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new ItemFamilyCode("A".repeat(21)));
    }
}
