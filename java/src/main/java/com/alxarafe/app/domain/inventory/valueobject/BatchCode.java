package com.alxarafe.app.domain.inventory.valueobject;

import java.util.Objects;

public record BatchCode(String value) {

    public BatchCode {
        Objects.requireNonNull(value, "BatchCode cannot be null.");
        if (value.isBlank()) {
            throw new IllegalArgumentException("BatchCode cannot be empty.");
        }
        if (value.length() > 30) {
            throw new IllegalArgumentException(
                    "BatchCode must not exceed 30 characters. Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }
}
