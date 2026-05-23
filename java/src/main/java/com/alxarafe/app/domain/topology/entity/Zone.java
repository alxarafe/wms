package com.alxarafe.app.domain.topology.entity;

import com.alxarafe.app.domain.topology.valueobject.NamingPolicy;
import com.alxarafe.app.domain.topology.valueobject.WarehouseId;
import com.alxarafe.app.domain.topology.valueobject.ZoneCode;
import com.alxarafe.app.domain.topology.valueobject.ZoneId;
import com.alxarafe.app.domain.topology.valueobject.ZoneTypeId;

import java.util.Objects;

/**
 * Entity representing a zone within a warehouse.
 *
 * A zone groups aisles and carries the NamingPolicy used
 * to generate composite location codes.
 */
public final class Zone {

    private final ZoneId id;
    private final WarehouseId warehouseId;
    private final ZoneTypeId zoneTypeId;
    private final ZoneCode code;
    private final NamingPolicy namingPolicy;

    public Zone(ZoneId id, WarehouseId warehouseId, ZoneTypeId zoneTypeId,
                ZoneCode code, NamingPolicy namingPolicy) {
        this.id = Objects.requireNonNull(id, "Zone id cannot be null.");
        this.warehouseId = Objects.requireNonNull(warehouseId, "Zone warehouseId cannot be null.");
        this.zoneTypeId = Objects.requireNonNull(zoneTypeId, "Zone zoneTypeId cannot be null.");
        this.code = Objects.requireNonNull(code, "Zone code cannot be null.");
        this.namingPolicy = Objects.requireNonNull(namingPolicy, "Zone namingPolicy cannot be null.");
    }

    public ZoneId id() {
        return id;
    }

    public WarehouseId warehouseId() {
        return warehouseId;
    }

    public ZoneTypeId zoneTypeId() {
        return zoneTypeId;
    }

    public ZoneCode code() {
        return code;
    }

    public NamingPolicy namingPolicy() {
        return namingPolicy;
    }
}
