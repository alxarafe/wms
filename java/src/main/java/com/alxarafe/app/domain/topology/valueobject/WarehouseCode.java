package com.alxarafe.app.domain.topology.valueobject;

import java.util.Objects;

/**
 * Value Object representing a unique warehouse code.
 */
public record WarehouseCode(String value) {

    public WarehouseCode {
        Objects.requireNonNull(value, "WarehouseCode cannot be null.");
        if (value.isBlank()) {
            throw new IllegalArgumentException("WarehouseCode cannot be empty.");
        }
        if (value.length() > 10) {
            throw new IllegalArgumentException(
                    "WarehouseCode must not exceed 10 characters. Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
