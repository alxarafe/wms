package com.alxarafe.app.domain.catalogue.valueobject;

import java.util.Objects;

/**
 * Value Object representing a Stock Keeping Unit code.
 */
public record Sku(String value) {

    public Sku {
        Objects.requireNonNull(value, "SKU cannot be null.");
        if (value.isBlank()) {
            throw new IllegalArgumentException("SKU cannot be empty.");
        }
        if (value.length() > 50) {
            throw new IllegalArgumentException(
                    "SKU must not exceed 50 characters. Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
