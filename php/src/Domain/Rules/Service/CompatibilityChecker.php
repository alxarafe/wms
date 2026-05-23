<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\Service;

use Alxarafe\App\Domain\Inventory\Entity\HandlingUnit;
use Alxarafe\App\Domain\Topology\Entity\Location;
use Alxarafe\App\Domain\Rules\Entity\CompatibilityRule;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeId;
use Alxarafe\App\Domain\Rules\ValueObject\RuleType;

/**
 * Domain Service responsible for checking compatibility rules.
 *
 * Ensures a HandlingUnit can be physically placed in a Location
 * without violating any active CompatibilityRules or capacity limits.
 */
final class CompatibilityChecker
{
    /**
     * Validates if a HandlingUnit can be placed in a Location.
     *
     * @param HandlingUnit $hu The unit being moved
     * @param Location $location The target location
     * @param array<string, AttributeId[]> $itemFamilyAttributes Map of itemId to its family attributes
     * @param AttributeId[] $locationAttributes Array of attributes associated with the location (or its aisle/zone)
     * @param CompatibilityRule[] $rules Active compatibility rules
     * @return bool True if compatible, false otherwise
     */
    public function validate(
        HandlingUnit $hu,
        Location $location,
        array $itemFamilyAttributes,
        array $locationAttributes,
        array $rules
    ): bool {
        // 1. If the location is blocked or disabled, no movement is allowed.
        if (!$location->isActive()) {
            return false;
        }

        // 2. Gather all item IDs inside the HU (including any nested quants)
        $quants = $hu->quants();
        if (count($quants) === 0) {
            return true; // Empty HU has no product-related compatibility conflicts
        }

        // Convert location attributes to a hash set for quick lookup
        $locAttrMap = [];
        foreach ($locationAttributes as $attrId) {
            $locAttrMap[$attrId->value()] = true;
        }

        // 3. For each quant's item, check its family attributes against the rules
        foreach ($quants as $quant) {
            $itemIdStr = $quant->itemId()->value();
            $familyAttrs = $itemFamilyAttributes[$itemIdStr] ?? [];

            foreach ($familyAttrs as $familyAttrId) {
                $famAttrStr = $familyAttrId->value();

                foreach ($rules as $rule) {
                    if ($rule->sourceAttributeId()->value() !== $famAttrStr) {
                        continue;
                    }

                    $targetAttrStr = $rule->targetAttributeId()->value();
                    $hasTargetAttr = isset($locAttrMap[$targetAttrStr]);

                    if ($rule->ruleType() === RuleType::REQUIRES && !$hasTargetAttr) {
                        // Source attribute requires target attribute, but location lacks it
                        return false;
                    }

                    if ($rule->ruleType() === RuleType::FORBIDS && $hasTargetAttr) {
                        // Source attribute forbids target attribute, but location has it
                        return false;
                    }
                }
            }
        }

        return true;
    }
}
