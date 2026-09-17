package com.alxarafe.domain.inventory.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.inventory.entity.Batch;
import com.alxarafe.app.domain.inventory.valueobject.BatchCode;
import com.alxarafe.app.domain.inventory.valueobject.BatchId;
import org.junit.jupiter.api.Test;

import java.time.Instant;

import static org.junit.jupiter.api.Assertions.*;

class BatchTest {

    @Test
    void create() {
        var id = new BatchId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var itemId = new ItemId("018e4e3a-3e7b-7b3e-8000-000000000002");
        var code = new BatchCode("BATCH-001");
        var expiration = Instant.parse("2027-01-01T00:00:00Z");
        var batch = new Batch(id, itemId, code, expiration);

        assertEquals(id, batch.id());
        assertEquals(itemId, batch.itemId());
        assertEquals(code, batch.batchCode());
        assertEquals(expiration, batch.expirationDate().orElseThrow());
    }

    @Test
    void createWithoutExpiration() {
        var batch = new Batch(
                new BatchId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ItemId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new BatchCode("BATCH-001"),
                null);
        assertTrue(batch.expirationDate().isEmpty());
    }

    @Test
    void isExpired() {
        var batch = new Batch(
                new BatchId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ItemId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new BatchCode("BATCH-001"),
                Instant.parse("2025-01-01T00:00:00Z"));
        assertTrue(batch.isExpired(Instant.parse("2026-01-01T00:00:00Z")));
    }

    @Test
    void isNotExpired() {
        var batch = new Batch(
                new BatchId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ItemId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new BatchCode("BATCH-001"),
                Instant.parse("2027-01-01T00:00:00Z"));
        assertFalse(batch.isExpired(Instant.parse("2026-01-01T00:00:00Z")));
    }

    @Test
    void noExpirationNeverExpired() {
        var batch = new Batch(
                new BatchId("018e4e3a-3e7b-7b3e-8000-000000000001"),
                new ItemId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new BatchCode("BATCH-001"),
                null);
        assertFalse(batch.isExpired(Instant.parse("2030-01-01T00:00:00Z")));
    }
}
