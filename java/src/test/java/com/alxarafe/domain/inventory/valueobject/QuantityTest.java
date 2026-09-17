package com.alxarafe.domain.inventory.valueobject;

import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class QuantityTest {

    @Test
    void create() {
        var qty = new Quantity(10.0, "EA");
        assertEquals(10.0, qty.value());
        assertEquals("EA", qty.unit());
    }

    @Test
    void zeroValue() {
        assertThrows(IllegalArgumentException.class, () -> new Quantity(0.0, "EA"));
    }

    @Test
    void negativeValue() {
        assertThrows(IllegalArgumentException.class, () -> new Quantity(-5.0, "EA"));
    }

    @Test
    void nullUnit() {
        assertThrows(NullPointerException.class, () -> new Quantity(1.0, null));
    }

    @Test
    void blankUnit() {
        assertThrows(IllegalArgumentException.class, () -> new Quantity(1.0, ""));
    }

    @Test
    void add() {
        var result = new Quantity(5.0, "EA").add(new Quantity(3.0, "EA"));
        assertEquals(8.0, result.value());
        assertEquals("EA", result.unit());
    }

    @Test
    void addDifferentUnit() {
        var a = new Quantity(5.0, "EA");
        assertThrows(IllegalArgumentException.class, () -> a.add(new Quantity(3.0, "KG")));
    }

    @Test
    void subtract() {
        var result = new Quantity(5.0, "EA").subtract(new Quantity(3.0, "EA"));
        assertEquals(2.0, result.value());
    }

    @Test
    void subtractInsufficient() {
        var a = new Quantity(3.0, "EA");
        assertThrows(IllegalArgumentException.class, () -> a.subtract(new Quantity(5.0, "EA")));
    }

    @Test
    void subtractDifferentUnit() {
        var a = new Quantity(5.0, "EA");
        assertThrows(IllegalArgumentException.class, () -> a.subtract(new Quantity(3.0, "KG")));
    }

    @Test
    void equals() {
        assertEquals(new Quantity(10.0, "EA"), new Quantity(10.0, "EA"));
        assertNotEquals(new Quantity(10.0, "EA"), new Quantity(5.0, "EA"));
    }
}
