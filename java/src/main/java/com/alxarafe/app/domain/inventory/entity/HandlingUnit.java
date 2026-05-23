package com.alxarafe.app.domain.inventory.entity;

import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.inventory.valueobject.BatchId;
import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.inventory.valueobject.HuStatus;
import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import com.alxarafe.app.domain.inventory.valueobject.Sscc;
import com.alxarafe.app.domain.inventory.valueobject.StockQuantId;
import com.alxarafe.app.domain.topology.valueobject.LocationId;

import java.util.ArrayList;
import java.util.Collections;
import java.util.List;
import java.util.Objects;
import java.util.Optional;

/**
 * Aggregate Root representing a Handling Unit (e.g., pallet, box, container).
 *
 * Can contain StockQuants or nest other Handling Units (parent-child relationship).
 */
public final class HandlingUnit {

    private final HandlingUnitId id;
    private final Sscc code;
    private LocationId locationId;
    private HandlingUnitId parentHuId;
    private HuStatus status;
    private final List<StockQuant> quants;

    public HandlingUnit(HandlingUnitId id, Sscc code, LocationId locationId,
                        HandlingUnitId parentHuId, HuStatus status) {
        this.id = Objects.requireNonNull(id, "HandlingUnit id cannot be null.");
        this.code = Objects.requireNonNull(code, "HandlingUnit code cannot be null.");
        this.locationId = locationId;
        this.parentHuId = parentHuId;
        this.status = Objects.requireNonNull(status, "HandlingUnit status cannot be null.");
        if (parentHuId != null && id.equals(parentHuId)) {
            throw new IllegalArgumentException("HandlingUnit cannot be its own parent.");
        }
        this.quants = new ArrayList<>();
    }

    public HandlingUnitId id() {
        return id;
    }

    public Sscc code() {
        return code;
    }

    public Optional<LocationId> locationId() {
        return Optional.ofNullable(locationId);
    }

    public Optional<HandlingUnitId> parentHuId() {
        return Optional.ofNullable(parentHuId);
    }

    public HuStatus status() {
        return status;
    }

    public List<StockQuant> quants() {
        return Collections.unmodifiableList(quants);
    }

    public boolean isAvailable() {
        return status == HuStatus.AVAILABLE;
    }

    public void moveToLocation(LocationId locationId) {
        this.locationId = Objects.requireNonNull(locationId, "LocationId cannot be null.");
        this.parentHuId = null;
    }

    public void nestIntoParent(HandlingUnitId parentHuId) {
        Objects.requireNonNull(parentHuId, "ParentHuId cannot be null.");
        if (this.id.equals(parentHuId)) {
            throw new IllegalArgumentException("HandlingUnit cannot be nested into itself.");
        }
        this.parentHuId = parentHuId;
        this.locationId = null;
    }

    public void releaseFromParent(LocationId newLocationId) {
        this.parentHuId = null;
        this.locationId = newLocationId;
    }

    public void updateStatus(HuStatus status) {
        this.status = Objects.requireNonNull(status, "Status cannot be null.");
    }

    /**
     * Adds an item to this Handling Unit.
     */
    public void addQuant(StockQuantId quantId, ItemId itemId, BatchId batchId, Quantity quantity) {
        Objects.requireNonNull(quantId, "StockQuantId cannot be null.");
        Objects.requireNonNull(itemId, "ItemId cannot be null.");
        Objects.requireNonNull(quantity, "Quantity cannot be null.");

        for (StockQuant quant : quants) {
            if (quant.itemId().equals(itemId) && equalsBatchId(quant.batchId().orElse(null), batchId)) {
                quant.add(quantity);
                return;
            }
        }

        quants.add(new StockQuant(quantId, this.id, itemId, batchId, quantity));
    }

    private static boolean equalsBatchId(BatchId a, BatchId b) {
        if (a == null && b == null) {
            return true;
        }
        if (a == null || b == null) {
            return false;
        }
        return a.equals(b);
    }
}
