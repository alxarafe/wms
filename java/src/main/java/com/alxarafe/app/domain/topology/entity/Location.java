package com.alxarafe.app.domain.topology.entity;

import com.alxarafe.app.domain.topology.valueobject.AisleId;
import com.alxarafe.app.domain.topology.valueobject.LocationCode;
import com.alxarafe.app.domain.topology.valueobject.LocationId;
import com.alxarafe.app.domain.topology.valueobject.LocationRole;
import com.alxarafe.app.domain.topology.valueobject.LocationStatus;

import java.util.Objects;

/**
 * Entity representing a specific storage location within an aisle.
 *
 * Identified by aisle + bay + level. The composite code is generated
 * by the LocationCodeGenerator domain service and persisted.
 */
public final class Location {

    private final LocationId id;
    private final AisleId aisleId;
    private final int bay;
    private final int level;
    private LocationCode code;
    private final LocationRole role;
    private LocationStatus status;

    public Location(LocationId id, AisleId aisleId, int bay, int level,
                    LocationCode code, LocationRole role, LocationStatus status) {
        this.id = Objects.requireNonNull(id, "Location id cannot be null.");
        this.aisleId = Objects.requireNonNull(aisleId, "Location aisleId cannot be null.");
        if (bay < 1) {
            throw new IllegalArgumentException("Location bay must be a positive integer. Got: " + bay);
        }
        if (level < 1) {
            throw new IllegalArgumentException("Location level must be a positive integer. Got: " + level);
        }
        this.bay = bay;
        this.level = level;
        this.code = Objects.requireNonNull(code, "Location code cannot be null.");
        this.role = Objects.requireNonNull(role, "Location role cannot be null.");
        this.status = Objects.requireNonNull(status, "Location status cannot be null.");
    }

    public LocationId id() {
        return id;
    }

    public AisleId aisleId() {
        return aisleId;
    }

    public int bay() {
        return bay;
    }

    public int level() {
        return level;
    }

    public LocationCode code() {
        return code;
    }

    public LocationRole role() {
        return role;
    }

    public LocationStatus status() {
        return status;
    }

    public boolean isActive() {
        return status == LocationStatus.ACTIVE;
    }

    public void block() {
        this.status = LocationStatus.BLOCKED;
    }

    public void activate() {
        this.status = LocationStatus.ACTIVE;
    }

    public void disable() {
        this.status = LocationStatus.DISABLED;
    }

    /**
     * Re-generates the location code using a new NamingPolicy.
     * Called when the zone's naming policy changes.
     */
    public void updateCode(LocationCode newCode) {
        this.code = Objects.requireNonNull(newCode, "New LocationCode cannot be null.");
    }
}
