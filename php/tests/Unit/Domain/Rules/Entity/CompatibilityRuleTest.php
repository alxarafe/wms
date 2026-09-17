<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\Entity;

use Alxarafe\App\Domain\Rules\Entity\CompatibilityRule;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeId;
use Alxarafe\App\Domain\Rules\ValueObject\CompatibilityRuleId;
use Alxarafe\App\Domain\Rules\ValueObject\RuleScope;
use Alxarafe\App\Domain\Rules\ValueObject\RuleType;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class CompatibilityRuleTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $source = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $target = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000003');
        $rule = new CompatibilityRule($id, RuleType::REQUIRES, $source, $target, RuleScope::LOCATION);

        self::assertTrue($id->equals($rule->id()));
        self::assertSame(RuleType::REQUIRES, $rule->ruleType());
        self::assertTrue($source->equals($rule->sourceAttributeId()));
        self::assertTrue($target->equals($rule->targetAttributeId()));
        self::assertSame(RuleScope::LOCATION, $rule->scope());
    }

    public function testSameSourceAndTarget(): void
    {
        $this->expectException(InvalidArgumentException::class);
        $attrId = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000001');
        new CompatibilityRule(
            new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            RuleType::FORBIDS,
            $attrId,
            $attrId,
            RuleScope::ZONE,
        );
    }
}
