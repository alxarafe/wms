package com.alxarafe.domain.topology.valueobject;

import com.alxarafe.app.domain.topology.valueobject.NamingPolicy;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class NamingPolicyTest {

    @Test
    void create() {
        var policy = new NamingPolicy("-", 2, 3, 2, 3, 2);
        assertEquals("-", policy.codeSeparator());
        assertEquals(2, policy.warehousePadding());
        assertEquals(3, policy.zonePadding());
        assertEquals(2, policy.aislePadding());
        assertEquals(3, policy.bayPadding());
        assertEquals(2, policy.levelPadding());
    }

    @Test
    void nullSeparator() {
        assertThrows(NullPointerException.class, () -> new NamingPolicy(null, 2, 3, 2, 3, 2));
    }

    @Test
    void blankSeparator() {
        assertThrows(IllegalArgumentException.class, () -> new NamingPolicy("", 2, 3, 2, 3, 2));
    }

    @Test
    void separatorTooLong() {
        assertThrows(IllegalArgumentException.class, () -> new NamingPolicy("----", 2, 3, 2, 3, 2));
    }

    @Test
    void paddingTooLow() {
        assertThrows(IllegalArgumentException.class, () -> new NamingPolicy("-", 0, 3, 2, 3, 2));
    }

    @Test
    void paddingTooHigh() {
        assertThrows(IllegalArgumentException.class, () -> new NamingPolicy("-", 11, 3, 2, 3, 2));
    }
}
