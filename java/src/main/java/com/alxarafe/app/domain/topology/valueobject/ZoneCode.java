package com.alxarafe.app.domain.topology.valueobject;

import java.util.Objects;

/**
 * Value Object representing a zone code within a warehouse.
 */
public record ZoneCode(String value) {

    public ZoneCode {
        Objects.requireNonNull(value, "ZoneCode cannot be null.");
        if (value.isBlank()) {
            throw new IllegalArgumentException("ZoneCode cannot be empty.");
        }
        if (value.length() > 20) {
            throw new IllegalArgumentException(
                    "ZoneCode must not exceed 20 characters. Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
