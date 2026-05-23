package com.alxarafe.app.domain.topology.valueobject;

import java.util.Objects;

/**
 * Value Object representing an aisle code within a zone.
 */
public record AisleCode(String value) {

    public AisleCode {
        Objects.requireNonNull(value, "AisleCode cannot be null.");
        if (value.isBlank()) {
            throw new IllegalArgumentException("AisleCode cannot be empty.");
        }
        if (value.length() > 20) {
            throw new IllegalArgumentException(
                    "AisleCode must not exceed 20 characters. Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
