package com.alxarafe.app.domain.inventory.valueobject;

import java.util.Objects;

public record Quantity(double value, String unit) {

    public Quantity {
        if (value <= 0.0) {
            throw new IllegalArgumentException("Quantity must be strictly positive. Got: " + value);
        }
        Objects.requireNonNull(unit, "Quantity unit cannot be null.");
        if (unit.isBlank()) {
            throw new IllegalArgumentException("Quantity unit cannot be empty.");
        }
    }

    public Quantity add(Quantity other) {
        Objects.requireNonNull(other, "Quantity to add cannot be null.");
        if (!this.unit.equals(other.unit)) {
            throw new IllegalArgumentException(
                    "Cannot add quantities with different units: " + this.unit + " vs " + other.unit);
        }
        return new Quantity(this.value + other.value, this.unit);
    }

    public Quantity subtract(Quantity other) {
        Objects.requireNonNull(other, "Quantity to subtract cannot be null.");
        if (!this.unit.equals(other.unit)) {
            throw new IllegalArgumentException(
                    "Cannot subtract quantities with different units: " + this.unit + " vs " + other.unit);
        }
        if (this.value < other.value) {
            throw new IllegalArgumentException(
                    "Insufficient quantity for subtraction. Has: " + this.value + ", wants: " + other.value);
        }
        return new Quantity(this.value - other.value, this.unit);
    }
}
