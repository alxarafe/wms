package com.alxarafe.app.domain.rules.event;

import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.rules.valueobject.MovementType;
import com.alxarafe.app.domain.rules.valueobject.StockMovementId;
import com.alxarafe.app.domain.topology.valueobject.LocationId;

import java.time.Instant;
import java.util.Objects;
import java.util.Optional;

/**
 * Domain Event and ledger representing a recorded movement of a Handling Unit.
 */
public final class StockMoved {

    private final StockMovementId id;
    private final MovementType type;
    private final HandlingUnitId huId;
    private final LocationId fromLocationId;
    private final LocationId toLocationId;
    private final Instant performedAt;

    public StockMoved(StockMovementId id, MovementType type, HandlingUnitId huId,
                      LocationId fromLocationId, LocationId toLocationId, Instant performedAt) {
        this.id = Objects.requireNonNull(id, "StockMoved id cannot be null.");
        this.type = Objects.requireNonNull(type, "StockMoved type cannot be null.");
        this.huId = Objects.requireNonNull(huId, "StockMoved huId cannot be null.");
        this.fromLocationId = fromLocationId; // Can be null for inbound movements
        this.toLocationId = Objects.requireNonNull(toLocationId, "StockMoved toLocationId cannot be null.");
        this.performedAt = Objects.requireNonNull(performedAt, "StockMoved performedAt cannot be null.");
    }

    public StockMovementId id() {
        return id;
    }

    public MovementType type() {
        return type;
    }

    public HandlingUnitId huId() {
        return huId;
    }

    public Optional<LocationId> fromLocationId() {
        return Optional.ofNullable(fromLocationId);
    }

    public LocationId toLocationId() {
        return toLocationId;
    }

    public Instant performedAt() {
        return performedAt;
    }
}
