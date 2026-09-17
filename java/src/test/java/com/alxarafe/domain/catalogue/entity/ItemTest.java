package com.alxarafe.domain.catalogue.entity;

import com.alxarafe.app.domain.catalogue.entity.Item;
import com.alxarafe.app.domain.catalogue.valueobject.ItemFamilyId;
import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.catalogue.valueobject.ItemUomConversion;
import com.alxarafe.app.domain.catalogue.valueobject.Money;
import com.alxarafe.app.domain.catalogue.valueobject.Sku;
import com.alxarafe.app.domain.catalogue.valueobject.UomId;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class ItemTest {

    private ItemId id;
    private Sku sku;
    private ItemFamilyId familyId;
    private UomId baseUomId;
    private Money baseCost;

    @BeforeEach
    void setUp() {
        id = new ItemId("018e4e3a-3e7b-7b3e-8000-000000000001");
        sku = new Sku("WIDGET-001");
        familyId = new ItemFamilyId("018e4e3a-3e7b-7b3e-8000-000000000002");
        baseUomId = new UomId("018e4e3a-3e7b-7b3e-8000-000000000003");
        baseCost = new Money(10.0, "EUR");
    }

    @Test
    void create() {
        var item = new Item(id, sku, "Widget", familyId, baseUomId, false, false, baseCost);
        assertEquals(id, item.id());
        assertEquals(sku, item.sku());
        assertEquals("Widget", item.name());
        assertEquals(familyId, item.familyId());
        assertEquals(baseUomId, item.baseUomId());
        assertFalse(item.isBatchManaged());
        assertFalse(item.isExpirable());
        assertEquals(baseCost, item.baseCost());
    }

    @Test
    void nullName() {
        assertThrows(NullPointerException.class, () ->
                new Item(id, sku, null, familyId, baseUomId, false, false, baseCost));
    }

    @Test
    void blankName() {
        assertThrows(IllegalArgumentException.class, () ->
                new Item(id, sku, "", familyId, baseUomId, false, false, baseCost));
    }

    @Test
    void expirableRequiresBatchManaged() {
        assertThrows(IllegalArgumentException.class, () ->
                new Item(id, sku, "Cheese", familyId, baseUomId, false, true, baseCost));
    }

    @Test
    void expirableWithBatchManaged() {
        var item = new Item(id, sku, "Cheese", familyId, baseUomId, true, true, baseCost);
        assertTrue(item.isBatchManaged());
        assertTrue(item.isExpirable());
    }

    @Test
    void addUomConversion() {
        var item = new Item(id, sku, "Widget", familyId, baseUomId, false, false, baseCost);
        var conversion = new ItemUomConversion(
                baseUomId,
                new UomId("018e4e3a-3e7b-7b3e-8000-000000000004"),
                12.0);
        item.addUomConversion(conversion);
        assertEquals(1, item.uomConversions().size());
    }

    @Test
    void addDuplicateUomConversion() {
        var item = new Item(id, sku, "Widget", familyId, baseUomId, false, false, baseCost);
        var toUomId = new UomId("018e4e3a-3e7b-7b3e-8000-000000000004");
        var conversion = new ItemUomConversion(baseUomId, toUomId, 12.0);
        item.addUomConversion(conversion);
        assertThrows(IllegalArgumentException.class, () -> item.addUomConversion(conversion));
    }
}
