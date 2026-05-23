package com.alxarafe.app.domain.rules.service;

import com.alxarafe.app.domain.inventory.entity.HandlingUnit;
import com.alxarafe.app.domain.inventory.entity.StockQuant;
import com.alxarafe.app.domain.topology.entity.Location;
import com.alxarafe.app.domain.rules.entity.CompatibilityRule;
import com.alxarafe.app.domain.rules.valueobject.AttributeId;
import com.alxarafe.app.domain.rules.valueobject.RuleType;

import java.util.*;

/**
 * Domain Service responsible for checking compatibility rules.
 *
 * Ensures a HandlingUnit can be physically placed in a Location
 * without violating any active CompatibilityRules or capacity limits.
 */
public final class CompatibilityChecker {

    /**
     * Validates if a HandlingUnit can be placed in a Location.
     *
     * @param hu                    the unit being moved
     * @param location              the target location
     * @param itemFamilyAttributes  map of itemId to its family attributes
     * @param locationAttributes    list of attributes associated with the location (or its aisle/zone)
     * @param rules                 active compatibility rules
     * @return true if compatible, false otherwise
     */
    public boolean validate(
            HandlingUnit hu,
            Location location,
            Map<String, List<AttributeId>> itemFamilyAttributes,
            List<AttributeId> locationAttributes,
            List<CompatibilityRule> rules
    ) {
        Objects.requireNonNull(hu, "HandlingUnit cannot be null.");
        Objects.requireNonNull(location, "Location cannot be null.");
        Objects.requireNonNull(itemFamilyAttributes, "itemFamilyAttributes cannot be null.");
        Objects.requireNonNull(locationAttributes, "locationAttributes cannot be null.");
        Objects.requireNonNull(rules, "rules cannot be null.");

        // 1. If the location is blocked or disabled, no movement is allowed.
        if (!location.isActive()) {
            return false;
        }

        // 2. Gather all quants inside the HU
        List<StockQuant> quants = hu.quants();
        if (quants.isEmpty()) {
            return true; // Empty HU has no product compatibility conflicts
        }

        // Convert location attributes to a set of string values for quick lookup
        Set<String> locAttrMap = new HashSet<>();
        for (AttributeId attrId : locationAttributes) {
            locAttrMap.add(attrId.value());
        }

        // 3. For each quant's item, check its family attributes against the rules
        for (StockQuant quant : quants) {
            String itemIdStr = quant.itemId().value();
            List<AttributeId> familyAttrs = itemFamilyAttributes.getOrDefault(itemIdStr, Collections.emptyList());

            for (AttributeId familyAttrId : familyAttrs) {
                String famAttrStr = familyAttrId.value();

                for (CompatibilityRule rule : rules) {
                    if (!rule.sourceAttributeId().value().equals(famAttrStr)) {
                        continue;
                    }

                    String targetAttrStr = rule.targetAttributeId().value();
                    boolean hasTargetAttr = locAttrMap.contains(targetAttrStr);

                    if (rule.ruleType() == RuleType.REQUIRES && !hasTargetAttr) {
                        // Source attribute requires target attribute, but location lacks it
                        return false;
                    }

                    if (rule.ruleType() == RuleType.FORBIDS && hasTargetAttr) {
                        // Source attribute forbids target attribute, but location has it
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
