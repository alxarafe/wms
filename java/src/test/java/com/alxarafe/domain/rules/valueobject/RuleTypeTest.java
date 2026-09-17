package com.alxarafe.domain.rules.valueobject;

import com.alxarafe.app.domain.rules.valueobject.RuleType;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class RuleTypeTest {

    @Test
    void values() {
        assertEquals(RuleType.REQUIRES, RuleType.valueOf("REQUIRES"));
        assertEquals(RuleType.FORBIDS, RuleType.valueOf("FORBIDS"));
        assertEquals(2, RuleType.values().length);
    }
}
