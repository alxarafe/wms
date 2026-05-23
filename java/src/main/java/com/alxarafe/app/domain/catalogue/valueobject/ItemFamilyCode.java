package com.alxarafe.app.domain.catalogue.valueobject;

import java.util.Objects;

/**
 * Value Object representing an item family code.
 */
public record ItemFamilyCode(String value) {

    public ItemFamilyCode {
        Objects.requireNonNull(value, "ItemFamilyCode cannot be null.");
        if (value.isBlank()) {
            throw new IllegalArgumentException("ItemFamilyCode cannot be empty.");
        }
        if (value.length() > 20) {
            throw new IllegalArgumentException(
                    "ItemFamilyCode must not exceed 20 characters. Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
