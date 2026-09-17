package com.alxarafe.domain.inventory.valueobject;

import com.alxarafe.app.domain.inventory.valueobject.BatchCode;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class BatchCodeTest {

    @Test
    void create() {
        var code = new BatchCode("BATCH-2026-001");
        assertEquals("BATCH-2026-001", code.value());
    }

    @Test
    void nullValue() {
        assertThrows(NullPointerException.class, () -> new BatchCode(null));
    }

    @Test
    void blank() {
        assertThrows(IllegalArgumentException.class, () -> new BatchCode(""));
    }

    @Test
    void tooLong() {
        assertThrows(IllegalArgumentException.class, () -> new BatchCode("A".repeat(31)));
    }
}
