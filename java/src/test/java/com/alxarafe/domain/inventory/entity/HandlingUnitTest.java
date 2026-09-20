package com.alxarafe.domain.inventory.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.inventory.entity.HandlingUnit;
import com.alxarafe.app.domain.inventory.valueobject.BatchId;
import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.inventory.valueobject.HuStatus;
import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import com.alxarafe.app.domain.inventory.valueobject.Sscc;
import com.alxarafe.app.domain.inventory.valueobject.StockQuantId;
import com.alxarafe.app.domain.topology.valueobject.LocationId;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class HandlingUnitTest {

    private HandlingUnitId id;
    private Sscc code;
    private HandlingUnitId parentId;

    @BeforeEach
    void setUp() {
        id = new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000001");
        code = new Sscc("123456789012345675");
        parentId = new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002");
    }

    @Test
    void create() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        assertEquals(id, hu.id());
        assertEquals(code, hu.code());
        assertTrue(hu.locationId().isEmpty());
        assertTrue(hu.parentHuId().isEmpty());
        assertEquals(HuStatus.AVAILABLE, hu.status());
    }

    @Test
    void cannotBeOwnParent() {
        assertThrows(IllegalArgumentException.class,
                () -> new HandlingUnit(id, code, null, id, HuStatus.AVAILABLE));
    }

    @Test
    void isAvailable() {
        assertTrue(new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE).isAvailable());
        assertFalse(new HandlingUnit(id, code, null, null, HuStatus.BLOCKED).isAvailable());
    }

    @Test
    void moveToLocation() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        var locId = new LocationId("018e4e3a-3e7b-7b3e-8000-000000000010");
        hu.moveToLocation(locId);
        assertEquals(locId, hu.locationId().orElseThrow());
        assertTrue(hu.parentHuId().isEmpty());
    }

    @Test
    void nestIntoParent() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        hu.nestIntoParent(parentId);
        assertEquals(parentId, hu.parentHuId().orElseThrow());
        assertTrue(hu.locationId().isEmpty());
    }

    @Test
    void nestIntoSelf() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        assertThrows(IllegalArgumentException.class, () -> hu.nestIntoParent(id));
    }

    @Test
    void releaseFromParent() {
        var hu = new HandlingUnit(id, code, null, parentId, HuStatus.AVAILABLE);
        var locId = new LocationId("018e4e3a-3e7b-7b3e-8000-000000000010");
        hu.releaseFromParent(locId);
        assertTrue(hu.parentHuId().isEmpty());
        assertEquals(locId, hu.locationId().orElseThrow());
    }

    @Test
    void updateStatus() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        hu.updateStatus(HuStatus.BLOCKED);
        assertEquals(HuStatus.BLOCKED, hu.status());
    }

    @Test
    void addQuant() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        var itemId = new ItemId("018e4e3a-3e7b-7b3e-8000-000000000020");
        var quantId = new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000030");
        hu.addQuant(quantId, itemId, null, Quantity.fromDecimal(10.0, "EA"));
        assertEquals(1, hu.quants().size());
    }

    @Test
    void addQuantMergesSameItemBatch() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        var itemId = new ItemId("018e4e3a-3e7b-7b3e-8000-000000000020");
        hu.addQuant(new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000030"), itemId, null, Quantity.fromDecimal(10.0, "EA"));
        hu.addQuant(new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000031"), itemId, null, Quantity.fromDecimal(5.0, "EA"));
        assertEquals(1, hu.quants().size());
        assertEquals(Quantity.fromDecimal(15.0, "EA"), hu.quants().getFirst().quantity());
    }

    @Test
    void addQuantDifferentBatch() {
        var hu = new HandlingUnit(id, code, null, null, HuStatus.AVAILABLE);
        var itemId = new ItemId("018e4e3a-3e7b-7b3e-8000-000000000020");
        var batch1 = new BatchId("018e4e3a-3e7b-7b3e-8000-000000000040");
        var batch2 = new BatchId("018e4e3a-3e7b-7b3e-8000-000000000041");
        hu.addQuant(new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000030"), itemId, batch1, Quantity.fromDecimal(10.0, "EA"));
        hu.addQuant(new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000031"), itemId, batch2, Quantity.fromDecimal(5.0, "EA"));
        assertEquals(2, hu.quants().size());
    }
}
