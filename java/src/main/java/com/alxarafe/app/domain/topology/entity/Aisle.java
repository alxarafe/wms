package com.alxarafe.app.domain.topology.entity;

import com.alxarafe.app.domain.topology.valueobject.AisleCode;
import com.alxarafe.app.domain.topology.valueobject.AisleId;
import com.alxarafe.app.domain.topology.valueobject.ZoneId;

import java.util.Objects;

/**
 * Entity representing a physical aisle within a zone.
 *
 * Aisles serve as the container for mass-blocking operations.
 * Blocking an aisle effectively blocks all locations within it.
 */
public final class Aisle {

    private final AisleId id;
    private final ZoneId zoneId;
    private final AisleCode code;
    private boolean blocked;

    public Aisle(AisleId id, ZoneId zoneId, AisleCode code) {
        this.id = Objects.requireNonNull(id, "Aisle id cannot be null.");
        this.zoneId = Objects.requireNonNull(zoneId, "Aisle zoneId cannot be null.");
        this.code = Objects.requireNonNull(code, "Aisle code cannot be null.");
        this.blocked = false;
    }

    public AisleId id() {
        return id;
    }

    public ZoneId zoneId() {
        return zoneId;
    }

    public AisleCode code() {
        return code;
    }

    public boolean isBlocked() {
        return blocked;
    }

    public void block() {
        this.blocked = true;
    }

    public void unblock() {
        this.blocked = false;
    }
}
