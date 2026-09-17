package com.alxarafe.domain.rules.valueobject;

import com.alxarafe.app.domain.rules.valueobject.TargetType;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class TargetTypeTest {

    @Test
    void values() {
        assertEquals(TargetType.LOCATION, TargetType.valueOf("LOCATION"));
        assertEquals(TargetType.FAMILY, TargetType.valueOf("FAMILY"));
        assertEquals(2, TargetType.values().length);
    }
}
