package com.alxarafe.app.domain.rules.entity;

import com.alxarafe.app.domain.rules.valueobject.AttributeId;
import com.alxarafe.app.domain.rules.valueobject.CompatibilityRuleId;
import com.alxarafe.app.domain.rules.valueobject.RuleScope;
import com.alxarafe.app.domain.rules.valueobject.RuleType;

import java.util.Objects;

/**
 * Entity representing a warehouse compatibility or storage constraint rule.
 *
 * Rules configure compatibility, e.g., a COLD merchandise attribute
 * REQUIRES a CHILLED location attribute at the LOCATION scope, or
 * a HAZMAT merchandise attribute FORBIDS a standard storage location.
 */
public final class CompatibilityRule {

    private final CompatibilityRuleId id;
    private final RuleType ruleType;
    private final AttributeId sourceAttributeId;
    private final AttributeId targetAttributeId;
    private final RuleScope scope;

    public CompatibilityRule(CompatibilityRuleId id, RuleType ruleType,
                             AttributeId sourceAttributeId, AttributeId targetAttributeId,
                             RuleScope scope) {
        this.id = Objects.requireNonNull(id, "CompatibilityRule id cannot be null.");
        this.ruleType = Objects.requireNonNull(ruleType, "CompatibilityRule ruleType cannot be null.");
        this.sourceAttributeId = Objects.requireNonNull(sourceAttributeId, "CompatibilityRule sourceAttributeId cannot be null.");
        this.targetAttributeId = Objects.requireNonNull(targetAttributeId, "CompatibilityRule targetAttributeId cannot be null.");
        this.scope = Objects.requireNonNull(scope, "CompatibilityRule scope cannot be null.");
        if (sourceAttributeId.equals(targetAttributeId)) {
            throw new IllegalArgumentException(
                    "Source and target attributes cannot be the same in a CompatibilityRule.");
        }
    }

    public CompatibilityRuleId id() {
        return id;
    }

    public RuleType ruleType() {
        return ruleType;
    }

    public AttributeId sourceAttributeId() {
        return sourceAttributeId;
    }

    public AttributeId targetAttributeId() {
        return targetAttributeId;
    }

    public RuleScope scope() {
        return scope;
    }
}
