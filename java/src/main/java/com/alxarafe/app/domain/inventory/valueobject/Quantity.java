package com.alxarafe.app.domain.inventory.valueobject;

import java.util.Objects;
import java.util.regex.Pattern;

/**
 * Quantity stored internally as a {@code long} scaled by 10^-SCALE to avoid
 * floating point drift (decisión M5).
 *
 * <p>Floating point is only used on the HTTP boundary
 * ({@link #fromDecimal(double, String)}), which rounds HALF_UP to the scale.
 * Arithmetic and database reads always work with exact integers
 * ({@link #fromScaled(long, String)} / {@link #fromDecimalString(String, String)}).
 *
 * <p>The unit is not part of the arithmetic: any unit is accepted, and two
 * quantities combined must share the same unit.
 */
public final class Quantity {

    public static final int SCALE = 6;
    private static final long SCALE_FACTOR = 1_000_000L;
    private static final Pattern DECIMAL = Pattern.compile("^(\\d+)(?:\\.(\\d+))?$");

    private final long scaledUnits;
    private final String unit;

    private Quantity(long scaledUnits, String unit) {
        if (scaledUnits <= 0) {
            throw new IllegalArgumentException("Quantity must be strictly positive.");
        }
        Objects.requireNonNull(unit, "Quantity unit cannot be null.");
        if (unit.isBlank()) {
            throw new IllegalArgumentException("Quantity unit cannot be empty.");
        }
        this.scaledUnits = scaledUnits;
        this.unit = unit;
    }

    /**
     * Creates a quantity from a decimal value (HTTP boundary, HALF_UP rounding).
     */
    public static Quantity fromDecimal(double value, String unit) {
        if (!Double.isFinite(value)) {
            throw new IllegalArgumentException("Quantity must be a finite number.");
        }
        if (value <= 0.0) {
            throw new IllegalArgumentException("Quantity must be strictly positive. Got: " + value);
        }
        long scaled = Math.round(value * SCALE_FACTOR);
        if (scaled <= 0) {
            throw new IllegalArgumentException("Quantity must be strictly positive. Got: " + value);
        }
        return new Quantity(scaled, unit);
    }

    /**
     * Creates a quantity from an already scaled integer (exact, no floating point).
     */
    public static Quantity fromScaled(long scaledUnits, String unit) {
        return new Quantity(scaledUnits, unit);
    }

    /**
     * Creates a quantity from a PostgreSQL NUMERIC(18,6) string like '30.500000'.
     */
    public static Quantity fromDecimalString(String decimal, String unit) {
        Objects.requireNonNull(decimal, "Decimal quantity cannot be null.");
        var matcher = DECIMAL.matcher(decimal);
        if (!matcher.matches()) {
            throw new IllegalArgumentException("Invalid decimal quantity: " + decimal);
        }
        long whole = Long.parseLong(matcher.group(1));
        String fraction = matcher.group(2) == null ? "" : matcher.group(2);
        if (fraction.length() > SCALE) {
            fraction = fraction.substring(0, SCALE);
        }
        fraction = fraction + "0".repeat(SCALE - fraction.length());
        long scaled = Math.multiplyExact(whole, SCALE_FACTOR) + Long.parseLong(fraction);
        return new Quantity(scaled, unit);
    }

    public long scaledUnits() {
        return scaledUnits;
    }

    public String unit() {
        return unit;
    }

    public double value() {
        return (double) scaledUnits / (double) SCALE_FACTOR;
    }

    /**
     * Exact decimal representation in NUMERIC(15,6) form for DB writes.
     */
    public String toDecimalString() {
        return String.format("%d.%06d", scaledUnits / SCALE_FACTOR, scaledUnits % SCALE_FACTOR);
    }

    public Quantity add(Quantity other) {
        Objects.requireNonNull(other, "Quantity to add cannot be null.");
        assertSameUnit(other);
        return new Quantity(this.scaledUnits + other.scaledUnits, this.unit);
    }

    public Quantity subtract(Quantity other) {
        Objects.requireNonNull(other, "Quantity to subtract cannot be null.");
        assertSameUnit(other);
        if (this.scaledUnits < other.scaledUnits) {
            throw new IllegalArgumentException(
                    "Insufficient quantity for subtraction. Has: " + this.value() + ", wants: " + other.value());
        }
        return new Quantity(this.scaledUnits - other.scaledUnits, this.unit);
    }

    private void assertSameUnit(Quantity other) {
        if (!this.unit.equals(other.unit)) {
            throw new IllegalArgumentException(
                    "Cannot combine quantities with different units: " + this.unit + " vs " + other.unit);
        }
    }

    @Override
    public boolean equals(Object other) {
        if (this == other) {
            return true;
        }
        if (!(other instanceof Quantity quantity)) {
            return false;
        }
        return scaledUnits == quantity.scaledUnits && unit.equals(quantity.unit);
    }

    @Override
    public int hashCode() {
        return Objects.hash(scaledUnits, unit);
    }

    @Override
    public String toString() {
        return value() + " " + unit;
    }
}