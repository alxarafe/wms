package com.alxarafe.app.domain.topology.valueobject;

import java.util.Objects;

/**
 * Value Object encapsulating the naming convention for generating location codes.
 *
 * Defines the separator and zero-padding widths for each segment
 * of a composite location code: warehouse-zone-aisle-bay-level.
 */
public record NamingPolicy(
        String codeSeparator,
        int warehousePadding,
        int zonePadding,
        int aislePadding,
        int bayPadding,
        int levelPadding
) {

    public NamingPolicy {
        Objects.requireNonNull(codeSeparator, "NamingPolicy codeSeparator cannot be null.");
        if (codeSeparator.isBlank()) {
            throw new IllegalArgumentException("NamingPolicy codeSeparator cannot be empty.");
        }
        if (codeSeparator.length() > 3) {
            throw new IllegalArgumentException(
                    "NamingPolicy codeSeparator must not exceed 3 characters.");
        }
        validatePadding("warehousePadding", warehousePadding);
        validatePadding("zonePadding", zonePadding);
        validatePadding("aislePadding", aislePadding);
        validatePadding("bayPadding", bayPadding);
        validatePadding("levelPadding", levelPadding);
    }

    private static void validatePadding(String name, int value) {
        if (value < 1 || value > 10) {
            throw new IllegalArgumentException(
                    "NamingPolicy " + name + " must be between 1 and 10. Got: " + value);
        }
    }
}
