package com.alxarafe.domain.catalogue.entity;

import com.alxarafe.app.domain.catalogue.entity.ItemFamily;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyCode;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ItemFamilyTest {

    @Test
    void create() {
        var id = new ItemFamilyId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var code = new ItemFamilyCode("ELECTRONICS");
        var family = new ItemFamily(id, code, "Consumer Electronics");

        assertEquals(id, family.id());
        assertEquals(code, family.code());
        assertEquals("Consumer Electronics", family.name());
    }

    @Test
    void nullName() {
        assertThrows(NullPointerException.class, () -> new ItemFamily(
                new ItemFamilyId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ItemFamilyCode("ELEC"),
                null));
    }

    @Test
    void blankName() {
        assertThrows(IllegalArgumentException.class, () -> new ItemFamily(
                new ItemFamilyId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ItemFamilyCode("ELEC"),
                ""));
    }
}
