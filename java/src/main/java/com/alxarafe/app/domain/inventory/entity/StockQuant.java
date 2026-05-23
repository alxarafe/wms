package com.alxarafe.app.domain.inventory.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.inventory.valueobject.BatchId;
import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import com.alxarafe.app.domain.inventory.valueobject.StockQuantId;

import java.util.Objects;
import java.util.Optional;

/**
 * Entity representing an indivisible quantity of stock of a particular item/batch
 * within a handling unit.
 */
public final class StockQuant {

    private final StockQuantId id;
    private final HandlingUnitId huId;
    private final ItemId itemId;
    private final BatchId batchId;
    private Quantity quantity;

    public StockQuant(StockQuantId id, HandlingUnitId huId, ItemId itemId, BatchId batchId, Quantity quantity) {
        this.id = Objects.requireNonNull(id, "StockQuant id cannot be null.");
        this.huId = Objects.requireNonNull(huId, "StockQuant huId cannot be null.");
        this.itemId = Objects.requireNonNull(itemId, "StockQuant itemId cannot be null.");
        this.batchId = batchId; // Batch can be null for non-batch-managed items
        this.quantity = Objects.requireNonNull(quantity, "StockQuant quantity cannot be null.");
    }

    public StockQuantId id() {
        return id;
    }

    public HandlingUnitId huId() {
        return huId;
    }

    public ItemId itemId() {
        return itemId;
    }

    public Optional<BatchId> batchId() {
        return Optional.ofNullable(batchId);
    }

    public Quantity quantity() {
        return quantity;
    }

    public void add(Quantity qty) {
        this.quantity = this.quantity.add(qty);
    }

    public void subtract(Quantity qty) {
        this.quantity = this.quantity.subtract(qty);
    }
}
