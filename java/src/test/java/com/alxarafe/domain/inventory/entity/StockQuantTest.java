package com.alxarafe.domain.inventory.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.inventory.entity.StockQuant;
import com.alxarafe.app.domain.inventory.valueobject.BatchId;
import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import com.alxarafe.app.domain.inventory.valueobject.StockQuantId;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class StockQuantTest {

    @Test
    void create() {
        var id = new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var huId = new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002");
        var itemId = new ItemId("018e4e3a-3e7b-7b3e-8000-000000000003");
        var qty = new Quantity(10.0, "EA");
        var quant = new StockQuant(id, huId, itemId, null, qty);

        assertEquals(id, quant.id());
        assertEquals(huId, quant.huId());
        assertEquals(itemId, quant.itemId());
        assertTrue(quant.batchId().isEmpty());
        assertEquals(qty, quant.quantity());
    }

    @Test
    void createWithBatch() {
        var batchId = new BatchId("018e4e3a-3e7b-7b3e-8000-000000000004");
        var quant = new StockQuant(
                new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new ItemId("018e4e3a-3e7b-7b3e-8000-000000000003"),
                batchId,
                new Quantity(5.0, "EA"));
        assertEquals(batchId, quant.batchId().orElseThrow());
    }

    @Test
    void add() {
        var quant = new StockQuant(
                new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new ItemId("018e4e3a-3e7b-7b3e-8000-000000000003"),
                null,
                new Quantity(10.0, "EA"));
        quant.add(new Quantity(5.0, "EA"));
        assertEquals(new Quantity(15.0, "EA"), quant.quantity());
    }

    @Test
    void subtract() {
        var quant = new StockQuant(
                new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new ItemId("018e4e3a-3e7b-7b3e-8000-000000000003"),
                null,
                new Quantity(10.0, "EA"));
        quant.subtract(new Quantity(3.0, "EA"));
        assertEquals(new Quantity(7.0, "EA"), quant.quantity());
    }
}
