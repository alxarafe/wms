package com.alxarafe.app.domain.inventory.valueobject;

import java.util.Objects;
import java.util.regex.Pattern;

/**
 * Value Object representing a Serial Shipping Container Code (SSCC-18).
 *
 * Validates that the input is exactly 18 digits and complies with
 * the standard GS1 check digit calculation.
 */
public record Sscc(String value) {

    private static final Pattern FORMAT = Pattern.compile("^\\d{18}$");

    public Sscc {
        Objects.requireNonNull(value, "SSCC cannot be null.");
        if (!FORMAT.matcher(value).matches()) {
            throw new IllegalArgumentException("SSCC must be exactly 18 digits. Got: " + value);
        }
        if (!validateCheckDigit(value)) {
            throw new IllegalArgumentException("SSCC has an invalid check digit. Got: " + value);
        }
    }

    @Override
    public String toString() {
        return value;
    }

    private static boolean validateCheckDigit(String sscc) {
        int sum = 0;
        // The first 17 digits are used for calculation
        // Alternating weights: even indices (0, 2, 4...) get 3, odd indices get 1.
        for (int i = 0; i < 17; i++) {
            int digit = Character.getNumericValue(sscc.charAt(i));
            int weight = (i % 2 == 0) ? 3 : 1;
            sum += digit * weight;
        }

        int remainder = sum % 10;
        int expectedCheckDigit = (remainder == 0) ? 0 : 10 - remainder;
        int actualCheckDigit = Character.getNumericValue(sscc.charAt(17));

        return expectedCheckDigit == actualCheckDigit;
    }
}
