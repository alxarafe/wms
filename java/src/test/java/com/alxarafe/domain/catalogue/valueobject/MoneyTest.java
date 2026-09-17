package com.alxarafe.domain.catalogue.valueobject;

import com.alxarafe.app.domain.catalogue.valueobject.Money;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class MoneyTest {

    @Test
    void createMoney() {
        var money = new Money(100.50, "EUR");
        assertEquals(100.50, money.amount());
        assertEquals("EUR", money.currency());
    }

    @Test
    void zeroAmount() {
        var money = new Money(0.0, "USD");
        assertEquals(0.0, money.amount());
    }

    @Test
    void negativeAmount() {
        assertThrows(IllegalArgumentException.class, () -> new Money(-1.0, "EUR"));
    }

    @Test
    void nullCurrency() {
        assertThrows(NullPointerException.class, () -> new Money(10.0, null));
    }

    @Test
    void invalidCurrencyTooShort() {
        assertThrows(IllegalArgumentException.class, () -> new Money(10.0, "EU"));
    }

    @Test
    void invalidCurrencyTooLong() {
        assertThrows(IllegalArgumentException.class, () -> new Money(10.0, "EURO"));
    }

    @Test
    void invalidCurrencyLowercase() {
        assertThrows(IllegalArgumentException.class, () -> new Money(10.0, "eur"));
    }

    @Test
    void equals() {
        var a = new Money(100.0, "EUR");
        var b = new Money(100.0, "EUR");
        assertEquals(a, b);
    }

    @Test
    void equalsDifferentAmount() {
        var a = new Money(100.0, "EUR");
        var b = new Money(200.0, "EUR");
        assertNotEquals(a, b);
    }

    @Test
    void equalsDifferentCurrency() {
        var a = new Money(100.0, "EUR");
        var b = new Money(100.0, "USD");
        assertNotEquals(a, b);
    }
}
