package com.alxarafe.domain.rules.valueobject;

import com.alxarafe.app.domain.rules.valueobject.RuleScope;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class RuleScopeTest {

    @Test
    void values() {
        assertEquals(RuleScope.LOCATION, RuleScope.valueOf("LOCATION"));
        assertEquals(RuleScope.AISLE, RuleScope.valueOf("AISLE"));
        assertEquals(RuleScope.ZONE, RuleScope.valueOf("ZONE"));
        assertEquals(3, RuleScope.values().length);
    }
}
