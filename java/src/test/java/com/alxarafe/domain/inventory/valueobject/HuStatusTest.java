package com.alxarafe.domain.inventory.valueobject;

import com.alxarafe.app.domain.inventory.valueobject.HuStatus;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class HuStatusTest {

    @Test
    void values() {
        assertEquals(HuStatus.AVAILABLE, HuStatus.valueOf("AVAILABLE"));
        assertEquals(HuStatus.IN_TRANSIT, HuStatus.valueOf("IN_TRANSIT"));
        assertEquals(HuStatus.BLOCKED, HuStatus.valueOf("BLOCKED"));
        assertEquals(3, HuStatus.values().length);
    }
}
