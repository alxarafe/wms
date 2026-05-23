package com.alxarafe.app.domain.inventory.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.inventory.valueobject.BatchCode;
import com.alxarafe.app.domain.inventory.valueobject.BatchId;

import java.time.Instant;
import java.util.Objects;
import java.util.Optional;

/**
 * Entity representing a specific manufactured/received batch of merchandise.
 */
public final class Batch {

    private final BatchId id;
    private final ItemId itemId;
    private final BatchCode batchCode;
    private final Instant expirationDate;

    public Batch(BatchId id, ItemId itemId, BatchCode batchCode, Instant expirationDate) {
        this.id = Objects.requireNonNull(id, "Batch id cannot be null.");
        this.itemId = Objects.requireNonNull(itemId, "Batch itemId cannot be null.");
        this.batchCode = Objects.requireNonNull(batchCode, "Batch batchCode cannot be null.");
        this.expirationDate = expirationDate;
    }

    public BatchId id() {
        return id;
    }

    public ItemId itemId() {
        return itemId;
    }

    public BatchCode batchCode() {
        return batchCode;
    }

    public Optional<Instant> expirationDate() {
        return Optional.ofNullable(expirationDate);
    }

    public boolean isExpired(Instant now) {
        if (expirationDate == null) {
            return false;
        }
        return expirationDate.isBefore(now);
    }
}
