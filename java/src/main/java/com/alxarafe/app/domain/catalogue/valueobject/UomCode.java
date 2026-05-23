package com.alxarafe.app.domain.catalogue.valueobject;

import java.util.Objects;
import java.util.regex.Pattern;

/**
 * Value Object representing a unit of measure code (EA, BOX, PAL, etc.).
 */
public record UomCode(String value) {

    private static final Pattern FORMAT = Pattern.compile("^[A-Z][A-Z0-9]{1,9}$");

    public UomCode {
        Objects.requireNonNull(value, "UomCode cannot be null.");
        if (!FORMAT.matcher(value).matches()) {
            throw new IllegalArgumentException(
                    "UomCode must be uppercase alphanumeric (2-10 chars). Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
