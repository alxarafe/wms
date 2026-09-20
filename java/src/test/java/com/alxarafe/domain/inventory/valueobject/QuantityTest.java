package com.alxarafe.domain.inventory.valueobject;

import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class QuantityTest {

    @Test
    void create() {
        var qty = Quantity.fromDecimal(10.0, "EA");
        assertEquals(10.0, qty.value());
        assertEquals("EA", qty.unit());
    }

    @Test
    void zeroValue() {
        assertThrows(IllegalArgumentException.class, () -> Quantity.fromDecimal(0.0, "EA"));
    }

    @Test
    void negativeValue() {
        assertThrows(IllegalArgumentException.class, () -> Quantity.fromDecimal(-5.0, "EA"));
    }

    @Test
    void nullUnit() {
        assertThrows(NullPointerException.class, () -> Quantity.fromDecimal(1.0, null));
    }

    @Test
    void blankUnit() {
        assertThrows(IllegalArgumentException.class, () -> Quantity.fromDecimal(1.0, ""));
    }

    @Test
    void add() {
        var result = Quantity.fromDecimal(5.0, "EA").add(Quantity.fromDecimal(3.0, "EA"));
        assertEquals(8.0, result.value());
        assertEquals("EA", result.unit());
    }

    @Test
    void addDifferentUnit() {
        var a = Quantity.fromDecimal(5.0, "EA");
        assertThrows(IllegalArgumentException.class, () -> a.add(Quantity.fromDecimal(3.0, "KG")));
    }

    @Test
    void subtract() {
        var result = Quantity.fromDecimal(5.0, "EA").subtract(Quantity.fromDecimal(3.0, "EA"));
        assertEquals(2.0, result.value());
    }

    @Test
    void subtractInsufficient() {
        var a = Quantity.fromDecimal(3.0, "EA");
        assertThrows(IllegalArgumentException.class, () -> a.subtract(Quantity.fromDecimal(5.0, "EA")));
    }

    @Test
    void subtractDifferentUnit() {
        var a = Quantity.fromDecimal(5.0, "EA");
        assertThrows(IllegalArgumentException.class, () -> a.subtract(Quantity.fromDecimal(3.0, "KG")));
    }

    @Test
    void equals() {
        assertEquals(Quantity.fromDecimal(10.0, "EA"), Quantity.fromDecimal(10.0, "EA"));
        assertNotEquals(Quantity.fromDecimal(10.0, "EA"), Quantity.fromDecimal(5.0, "EA"));
    }

    @Test
    void scaledRoundTrip() {
        var qty = Quantity.fromScaled(30_500_001L, "EA");
        assertEquals(30_500_001L, qty.scaledUnits());
        assertEquals(30.500001, qty.value());
    }

    @Test
    void decimalStringRoundTrip() {
        var qty = Quantity.fromDecimalString("30.50025", "EA");
        assertEquals(30_500_250L, qty.scaledUnits());
        assertEquals("30.500250", qty.toDecimalString());
        assertEquals(30.50025, qty.value());
    }

    @Test
    void exactAdditionUsesScaledUnits() {
        var sum = Quantity.fromDecimal(0.1, "EA").add(Quantity.fromDecimal(0.2, "EA"));
        assertEquals(0.3, sum.value());
    }

    @Test
    void roundsHalfUpOnBoundary() {
        var qty = Quantity.fromDecimal(2.5000004, "EA");
        assertEquals(2_500_000L, qty.scaledUnits());
        assertEquals(2.5, qty.value());
    }

    @Test
    void rejectsNonFinite() {
        assertThrows(IllegalArgumentException.class, () -> Quantity.fromDecimal(Double.NaN, "EA"));
        assertThrows(IllegalArgumentException.class, () -> Quantity.fromDecimal(Double.POSITIVE_INFINITY, "EA"));
    }

    @Test
    void rejectsInvalidDecimalString() {
        assertThrows(IllegalArgumentException.class, () -> Quantity.fromDecimalString("abc", "EA"));
    }
}
