package com.alxarafe.domain.rules.service;

import com.alxarafe.app.domain.catalogue.valueobject.ItemId;
import com.alxarafe.app.domain.inventory.entity.HandlingUnit;
import com.alxarafe.app.domain.inventory.valueobject.HandlingUnitId;
import com.alxarafe.app.domain.inventory.valueobject.HuStatus;
import com.alxarafe.app.domain.inventory.valueobject.Quantity;
import com.alxarafe.app.domain.inventory.valueobject.Sscc;
import com.alxarafe.app.domain.inventory.valueobject.StockQuantId;
import com.alxarafe.app.domain.rules.entity.CompatibilityRule;
import com.alxarafe.app.domain.rules.service.CompatibilityChecker;
import com.alxarafe.app.domain.rules.valueobject.AttributeId;
import com.alxarafe.app.domain.rules.valueobject.CompatibilityRuleId;
import com.alxarafe.app.domain.rules.valueobject.RuleScope;
import com.alxarafe.app.domain.rules.valueobject.RuleType;
import com.alxarafe.app.domain.topology.entity.Location;
import com.alxarafe.app.domain.topology.valueobject.AisleId;
import com.alxarafe.app.domain.topology.valueobject.LocationCode;
import com.alxarafe.app.domain.topology.valueobject.LocationId;
import com.alxarafe.app.domain.topology.valueobject.LocationRole;
import com.alxarafe.app.domain.topology.valueobject.LocationStatus;
import org.junit.jupiter.api.BeforeEach;
import org.junit.jupiter.api.Test;

import java.util.List;
import java.util.Map;

import static org.junit.jupiter.api.Assertions.*;

class CompatibilityCheckerTest {

    private CompatibilityChecker checker;
    private AttributeId coldAttr;
    private AttributeId frozenAttr;
    private AttributeId refrigeratedAttr;
    private Location activeLocation;
    private Location inactiveLocation;

    @BeforeEach
    void setUp() {
        checker = new CompatibilityChecker();
        coldAttr = new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000001");
        frozenAttr = new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000002");
        refrigeratedAttr = new AttributeId("018e4e3a-3e7b-7b3e-8000-000000000003");

        var aisleId = new AisleId("018e4e3a-3e7b-7b3e-8000-000000000010");
        activeLocation = new Location(
                new LocationId("018e4e3a-3e7b-7b3e-8000-000000000011"),
                aisleId, 1, 1,
                new LocationCode("WH01-A01-01-01"),
                LocationRole.PICKING,
                LocationStatus.ACTIVE);
        inactiveLocation = new Location(
                new LocationId("018e4e3a-3e7b-7b3e-8000-000000000012"),
                aisleId, 1, 1,
                new LocationCode("WH01-A01-01-02"),
                LocationRole.PICKING,
                LocationStatus.BLOCKED);
    }

    private HandlingUnit createHuWithItem(String itemIdStr, String quantIdStr) {
        var huId = new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000020");
        var hu = new HandlingUnit(huId, new Sscc("123456789012345675"), null, null, HuStatus.AVAILABLE);
        hu.addQuant(
                new StockQuantId(quantIdStr),
                new ItemId(itemIdStr),
                null,
                Quantity.fromDecimal(1.0, "EA"));
        return hu;
    }

    @Test
    void inactiveLocationReturnsFalse() {
        var hu = createHuWithItem("018e4e3a-3e7b-7b3e-8000-000000000100",
                "018e4e3a-3e7b-7b3e-8000-000000000030");
        assertFalse(checker.validate(hu, inactiveLocation, Map.of(), List.of(), List.of()));
    }

    @Test
    void emptyHuReturnsTrue() {
        var hu = new HandlingUnit(
                new HandlingUnitId("018e4e3a-3e7b-7b3e-8000-000000000020"),
                new Sscc("123456789012345675"),
                null, null, HuStatus.AVAILABLE);
        assertTrue(checker.validate(hu, activeLocation, Map.of(), List.of(), List.of()));
    }

    @Test
    void requiresRuleSatisfied() {
        var hu = createHuWithItem("018e4e3a-3e7b-7b3e-8000-000000000100",
                "018e4e3a-3e7b-7b3e-8000-000000000030");
        var itemFamilyAttrs = Map.of("018e4e3a-3e7b-7b3e-8000-000000000100", List.of(coldAttr));
        var locationAttrs = List.of(refrigeratedAttr);
        var rules = List.of(new CompatibilityRule(
                new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000050"),
                RuleType.REQUIRES, coldAttr, refrigeratedAttr, RuleScope.LOCATION));
        assertTrue(checker.validate(hu, activeLocation, itemFamilyAttrs, locationAttrs, rules));
    }

    @Test
    void requiresRuleNotSatisfied() {
        var hu = createHuWithItem("018e4e3a-3e7b-7b3e-8000-000000000100",
                "018e4e3a-3e7b-7b3e-8000-000000000030");
        var itemFamilyAttrs = Map.of("018e4e3a-3e7b-7b3e-8000-000000000100", List.of(coldAttr));
        var locationAttrs = List.<AttributeId>of();
        var rules = List.of(new CompatibilityRule(
                new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000050"),
                RuleType.REQUIRES, coldAttr, refrigeratedAttr, RuleScope.LOCATION));
        assertFalse(checker.validate(hu, activeLocation, itemFamilyAttrs, locationAttrs, rules));
    }

    @Test
    void forbidsRuleNotViolated() {
        var hu = createHuWithItem("018e4e3a-3e7b-7b3e-8000-000000000100",
                "018e4e3a-3e7b-7b3e-8000-000000000030");
        var itemFamilyAttrs = Map.of("018e4e3a-3e7b-7b3e-8000-000000000100", List.of(coldAttr));
        var locationAttrs = List.<AttributeId>of();
        var rules = List.of(new CompatibilityRule(
                new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000050"),
                RuleType.FORBIDS, coldAttr, frozenAttr, RuleScope.LOCATION));
        assertTrue(checker.validate(hu, activeLocation, itemFamilyAttrs, locationAttrs, rules));
    }

    @Test
    void forbidsRuleViolated() {
        var hu = createHuWithItem("018e4e3a-3e7b-7b3e-8000-000000000100",
                "018e4e3a-3e7b-7b3e-8000-000000000030");
        var itemFamilyAttrs = Map.of("018e4e3a-3e7b-7b3e-8000-000000000100", List.of(coldAttr));
        var locationAttrs = List.of(frozenAttr);
        var rules = List.of(new CompatibilityRule(
                new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000050"),
                RuleType.FORBIDS, coldAttr, frozenAttr, RuleScope.LOCATION));
        assertFalse(checker.validate(hu, activeLocation, itemFamilyAttrs, locationAttrs, rules));
    }

    @Test
    void multipleItemsMultipleRules() {
        var hu = createHuWithItem("018e4e3a-3e7b-7b3e-8000-000000000100",
                "018e4e3a-3e7b-7b3e-8000-000000000030");
        var itemId2 = "018e4e3a-3e7b-7b3e-8000-000000000101";
        hu.addQuant(
                new StockQuantId("018e4e3a-3e7b-7b3e-8000-000000000031"),
                new ItemId(itemId2), null, Quantity.fromDecimal(2.0, "EA"));

        var itemFamilyAttrs = Map.of(
                "018e4e3a-3e7b-7b3e-8000-000000000100", List.of(coldAttr),
                itemId2, List.of(frozenAttr));
        var locationAttrs = List.of(refrigeratedAttr);
        var rules = List.of(
                new CompatibilityRule(
                        new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000050"),
                        RuleType.REQUIRES, coldAttr, refrigeratedAttr, RuleScope.LOCATION),
                new CompatibilityRule(
                        new CompatibilityRuleId("018e4e3a-3e7b-7b3e-8000-000000000051"),
                        RuleType.REQUIRES, frozenAttr, refrigeratedAttr, RuleScope.LOCATION));
        assertTrue(checker.validate(hu, activeLocation, itemFamilyAttrs, locationAttrs, rules));
    }
}
