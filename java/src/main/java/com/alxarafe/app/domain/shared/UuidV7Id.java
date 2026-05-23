package com.alxarafe.app.domain.shared;

import java.util.Objects;
import java.util.regex.Pattern;

/**
 * Abstract base class for typed UUID v7 identifiers.
 *
 * Prevents primitive obsession by wrapping UUID strings
 * in strongly-typed value objects.
 */
public abstract class UuidV7Id {

    private static final Pattern UUID_PATTERN =
            Pattern.compile("^[0-9a-f]{8}-[0-9a-f]{4}-7[0-9a-f]{3}-[89ab][0-9a-f]{3}-[0-9a-f]{12}$",
                    Pattern.CASE_INSENSITIVE);

    private final String value;

    protected UuidV7Id(String value) {
        Objects.requireNonNull(value, getClass().getSimpleName() + " cannot be null.");
        if (value.isBlank()) {
            throw new IllegalArgumentException(getClass().getSimpleName() + " cannot be empty.");
        }
        if (!UUID_PATTERN.matcher(value).matches()) {
            throw new IllegalArgumentException(
                    getClass().getSimpleName() + " must be a valid UUID v7. Got: " + value);
        }
        this.value = value;
    }

    public String value() {
        return value;
    }

    @Override
    public boolean equals(Object o) {
        if (this == o) return true;
        if (o == null || getClass() != o.getClass()) return false;
        UuidV7Id that = (UuidV7Id) o;
        return value.equals(that.value);
    }

    @Override
    public int hashCode() {
        return Objects.hash(getClass(), value);
    }

    @Override
    public String toString() {
        return value;
    }
}
