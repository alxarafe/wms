package com.alxarafe.domain.rules.entity;

import com.alxarafe.app.domain.rules.entity.CompatibilityRule;
import com.alxarafe.app.domain.rules.valueobject.AttributeId;
import com.alxarafe.app.domain.rules.valueobject.CompatibilityRuleId;
import com.alxarafe.app.domain.rules.valueobject.RuleScope;
import com.alxarafe.app.domain.rules.valueobject.RuleType;
import org.junit.jupiter.api.Test;

import static org.junit.jupiter.api.Assertions.*;

class CompatibilityRuleTest {

    @Test
    void create() {
        var id = new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000001");
        var source = new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000002");
        var target = new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000003");
        var rule = new CompatibilityRule(id, RuleType.REQUIRES, source, target, RuleScope.LOCATION);

        assertEquals(id, rule.id());
        assertEquals(RuleType.REQUIRES, rule.ruleType());
        assertEquals(source, rule.sourceAttributeId());
        assertEquals(target, rule.targetAttributeId());
        assertEquals(RuleScope.LOCATION, rule.scope());
    }

    @Test
    void sameSourceAndTarget() {
        var attrId = new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000001");
        assertThrows(IllegalArgumentException.class, () -> new CompatibilityRule(
                new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                RuleType.FORBIDS,
                attrId,
                attrId,
                RuleScope.ZONE));
    }

    @Test
    void nullId() {
        assertThrows(NullPointerException.class, () -> new CompatibilityRule(
                null, RuleType.REQUIRES,
                new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000002"),
                new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000003"),
                RuleScope.LOCATION));
    }
}
