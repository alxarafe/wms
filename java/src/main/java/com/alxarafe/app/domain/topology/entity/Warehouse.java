package com.alxarafe.app.domain.topology.entity;

import com.alxarafe.app.domain.topology.valueobject.WarehouseCode;
import com.alxarafe.app.domain.topology.valueobject.WarehouseId;

import java.util.Objects;

/**
 * Aggregate Root representing a physical warehouse.
 */
public final class Warehouse {

    private final WarehouseId id;
    private final WarehouseCode code;
    private final String name;

    public Warehouse(WarehouseId id, WarehouseCode code, String name) {
        this.id = Objects.requireNonNull(id, "Warehouse id cannot be null.");
        this.code = Objects.requireNonNull(code, "Warehouse code cannot be null.");
        Objects.requireNonNull(name, "Warehouse name cannot be null.");
        if (name.isBlank()) {
            throw new IllegalArgumentException("Warehouse name cannot be empty.");
        }
        this.name = name;
    }

    public WarehouseId id() {
        return id;
    }

    public WarehouseCode code() {
        return code;
    }

    public String name() {
        return name;
    }
}
