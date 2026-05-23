package com.alxarafe.app.domain.topology.valueobject;

import java.util.Objects;
import java.util.regex.Pattern;

/**
 * Value Object representing a zone type code (e.g. PICKING, BULK, RETURNS).
 */
public record ZoneTypeCode(String value) {

    private static final Pattern FORMAT = Pattern.compile("^[A-Z][A-Z0-9_]{1,19}$");

    public ZoneTypeCode {
        Objects.requireNonNull(value, "ZoneTypeCode cannot be null.");
        if (!FORMAT.matcher(value).matches()) {
            throw new IllegalArgumentException(
                    "ZoneTypeCode must be uppercase alphanumeric with underscores (2-20 chars). Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
