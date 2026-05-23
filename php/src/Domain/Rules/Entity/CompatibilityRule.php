<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\Entity;

use Alxarafe\App\Domain\Rules\ValueObject\AttributeId;
use Alxarafe\App\Domain\Rules\ValueObject\CompatibilityRuleId;
use Alxarafe\App\Domain\Rules\ValueObject\RuleScope;
use Alxarafe\App\Domain\Rules\ValueObject\RuleType;
use InvalidArgumentException;

/**
 * Entity representing a warehouse compatibility or storage constraint rule.
 *
 * Rules configure compatibility, e.g., a COLD merchandise attribute
 * REQUIRES a REFRIGERATED location attribute at the LOCATION scope, or
 * a HAZMAT merchandise attribute FORBIDS a standard storage location.
 */
final readonly class CompatibilityRule
{
    public function __construct(
        private CompatibilityRuleId $id,
        private RuleType $ruleType,
        private AttributeId $sourceAttributeId, // Usually an ItemFamily attribute
        private AttributeId $targetAttributeId, // Usually a Location attribute
        private RuleScope $scope,
    ) {
        if ($sourceAttributeId->equals($targetAttributeId)) {
            throw new InvalidArgumentException(
                'Source and target attributes cannot be the same in a CompatibilityRule.'
            );
        }
    }

    public function id(): CompatibilityRuleId
    {
        return $this->id;
    }

    public function ruleType(): RuleType
    {
        return $this->ruleType;
    }

    public function sourceAttributeId(): AttributeId
    {
        return $this->sourceAttributeId;
    }

    public function targetAttributeId(): AttributeId
    {
        return $this->targetAttributeId;
    }

    public function scope(): RuleScope
    {
        return $this->scope;
    }
}
