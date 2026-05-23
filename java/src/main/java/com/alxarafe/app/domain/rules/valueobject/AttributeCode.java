package com.alxarafe.app.domain.rules.valueobject;

import java.util.Objects;
import java.util.regex.Pattern;

/**
 * Value Object representing an attribute code (e.g. COLD, HAZMAT, FRAGILE).
 */
public record AttributeCode(String value) {

    private static final Pattern FORMAT = Pattern.compile("^[A-Z][A-Z0-9_]{1,19}$");

    public AttributeCode {
        Objects.requireNonNull(value, "AttributeCode cannot be null.");
        if (!FORMAT.matcher(value).matches()) {
            throw new IllegalArgumentException(
                    "AttributeCode must be uppercase alphanumeric with underscores (2-20 chars). Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
