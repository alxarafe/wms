package com.alxarafe.app.domain.catalogue.valueobject;

import java.util.Objects;
import java.util.regex.Pattern;

/**
 * Value Object representing a monetary amount with currency.
 */
public record Money(double amount, String currency) {

    private static final Pattern ISO_4217 = Pattern.compile("^[A-Z]{3}$");

    public Money {
        if (amount < 0.0) {
            throw new IllegalArgumentException(
                    "Money amount cannot be negative. Got: " + amount);
        }
        Objects.requireNonNull(currency, "Money currency cannot be null.");
        if (!ISO_4217.matcher(currency).matches()) {
            throw new IllegalArgumentException(
                    "Money currency must be a 3-letter ISO 4217 code. Got: " + currency);
        }
    }
}
