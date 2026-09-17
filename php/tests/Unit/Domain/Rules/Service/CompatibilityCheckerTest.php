<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\Service;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Inventory\Entity\HandlingUnit;
use Alxarafe\App\Domain\Inventory\ValueObject\HandlingUnitId;
use Alxarafe\App\Domain\Inventory\ValueObject\HuStatus;
use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use Alxarafe\App\Domain\Inventory\ValueObject\Sscc;
use Alxarafe\App\Domain\Inventory\ValueObject\StockQuantId;
use Alxarafe\App\Domain\Rules\Entity\CompatibilityRule;
use Alxarafe\App\Domain\Rules\Service\CompatibilityChecker;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeId;
use Alxarafe\App\Domain\Rules\ValueObject\CompatibilityRuleId;
use Alxarafe\App\Domain\Rules\ValueObject\RuleScope;
use Alxarafe\App\Domain\Rules\ValueObject\RuleType;
use Alxarafe\App\Domain\Topology\Entity\Location;
use Alxarafe\App\Domain\Topology\ValueObject\AisleId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationCode;
use Alxarafe\App\Domain\Topology\ValueObject\LocationId;
use Alxarafe\App\Domain\Topology\ValueObject\LocationRole;
use Alxarafe\App\Domain\Topology\ValueObject\LocationStatus;
use PHPUnit\Framework\TestCase;

final class CompatibilityCheckerTest extends TestCase
{
    private CompatibilityChecker $checker;
    private AttributeId $coldAttr;
    private AttributeId $frozenAttr;
    private AttributeId $refrigeratedAttr;
    private Location $activeLocation;
    private Location $inactiveLocation;

    protected function setUp(): void
    {
        $this->checker = new CompatibilityChecker();
        $this->coldAttr = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $this->frozenAttr = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $this->refrigeratedAttr = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000003');

        $aisleId = new AisleId('018e4e3a-3e7b-7b3e-8000-000000000010');
        $this->activeLocation = new Location(
            new LocationId('018e4e3a-3e7b-7b3e-8000-000000000011'),
            $aisleId,
            1,
            1,
            new LocationCode('WH01-A01-01-01'),
            LocationRole::PICKING,
            LocationStatus::ACTIVE,
        );
        $this->inactiveLocation = new Location(
            new LocationId('018e4e3a-3e7b-7b3e-8000-000000000012'),
            $aisleId,
            1,
            1,
            new LocationCode('WH01-A01-01-02'),
            LocationRole::PICKING,
            LocationStatus::BLOCKED,
        );
    }

    private function createHuWithItem(string $itemIdStr, ?string $quantIdStr = null): HandlingUnit
    {
        $huId = new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000020');
        $hu = new HandlingUnit($huId, new Sscc('123456789012345675'), null, null, HuStatus::AVAILABLE);
        $hu->addQuant(
            new StockQuantId($quantIdStr ?? '018e4e3a-3e7b-7b3e-8000-000000000030'),
            new ItemId($itemIdStr),
            null,
            new Quantity(1.0, 'EA'),
        );
        return $hu;
    }

    public function testInactiveLocationReturnsFalse(): void
    {
        $hu = $this->createHuWithItem('018e4e3a-3e7b-7b3e-8000-000000000100');
        $result = $this->checker->validate($hu, $this->inactiveLocation, [], [], []);
        self::assertFalse($result);
    }

    public function testEmptyHuReturnsTrue(): void
    {
        $hu = new HandlingUnit(
            new HandlingUnitId('018e4e3a-3e7b-7b3e-8000-000000000020'),
            new Sscc('123456789012345675'),
            null,
            null,
            HuStatus::AVAILABLE,
        );
        $result = $this->checker->validate($hu, $this->activeLocation, [], [], []);
        self::assertTrue($result);
    }

    public function testRequiresRuleSatisfied(): void
    {
        $hu = $this->createHuWithItem('018e4e3a-3e7b-7b3e-8000-000000000100');
        $itemFamilyAttrs = ['018e4e3a-3e7b-7b3e-8000-000000000100' => [$this->coldAttr]];
        $locationAttrs = [$this->refrigeratedAttr];
        $rules = [
            new CompatibilityRule(
                new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000050'),
                RuleType::REQUIRES,
                $this->coldAttr,
                $this->refrigeratedAttr,
                RuleScope::LOCATION,
            ),
        ];
        $result = $this->checker->validate($hu, $this->activeLocation, $itemFamilyAttrs, $locationAttrs, $rules);
        self::assertTrue($result);
    }

    public function testRequiresRuleNotSatisfied(): void
    {
        $hu = $this->createHuWithItem('018e4e3a-3e7b-7b3e-8000-000000000100');
        $itemFamilyAttrs = ['018e4e3a-3e7b-7b3e-8000-000000000100' => [$this->coldAttr]];
        $locationAttrs = [];
        $rules = [
            new CompatibilityRule(
                new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000050'),
                RuleType::REQUIRES,
                $this->coldAttr,
                $this->refrigeratedAttr,
                RuleScope::LOCATION,
            ),
        ];
        $result = $this->checker->validate($hu, $this->activeLocation, $itemFamilyAttrs, $locationAttrs, $rules);
        self::assertFalse($result);
    }

    public function testForbidsRuleNotViolated(): void
    {
        $hu = $this->createHuWithItem('018e4e3a-3e7b-7b3e-8000-000000000100');
        $itemFamilyAttrs = ['018e4e3a-3e7b-7b3e-8000-000000000100' => [$this->coldAttr]];
        $locationAttrs = [];
        $rules = [
            new CompatibilityRule(
                new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000050'),
                RuleType::FORBIDS,
                $this->coldAttr,
                $this->frozenAttr,
                RuleScope::LOCATION,
            ),
        ];
        $result = $this->checker->validate($hu, $this->activeLocation, $itemFamilyAttrs, $locationAttrs, $rules);
        self::assertTrue($result);
    }

    public function testForbidsRuleViolated(): void
    {
        $hu = $this->createHuWithItem('018e4e3a-3e7b-7b3e-8000-000000000100');
        $itemFamilyAttrs = ['018e4e3a-3e7b-7b3e-8000-000000000100' => [$this->coldAttr]];
        $locationAttrs = [$this->frozenAttr];
        $rules = [
            new CompatibilityRule(
                new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000050'),
                RuleType::FORBIDS,
                $this->coldAttr,
                $this->frozenAttr,
                RuleScope::LOCATION,
            ),
        ];
        $result = $this->checker->validate($hu, $this->activeLocation, $itemFamilyAttrs, $locationAttrs, $rules);
        self::assertFalse($result);
    }

    public function testMultipleItemsMultipleRules(): void
    {
        $hu = $this->createHuWithItem('018e4e3a-3e7b-7b3e-8000-000000000100');
        $itemId2 = '018e4e3a-3e7b-7b3e-8000-000000000101';
        $hu->addQuant(
            new StockQuantId('018e4e3a-3e7b-7b3e-8000-000000000031'),
            new ItemId($itemId2),
            null,
            new Quantity(2.0, 'EA'),
        );

        $itemFamilyAttrs = [
            '018e4e3a-3e7b-7b3e-8000-000000000100' => [$this->coldAttr],
            $itemId2 => [$this->frozenAttr],
        ];
        $locationAttrs = [$this->refrigeratedAttr];
        $rules = [
            new CompatibilityRule(
                new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000050'),
                RuleType::REQUIRES,
                $this->coldAttr,
                $this->refrigeratedAttr,
                RuleScope::LOCATION,
            ),
            new CompatibilityRule(
                new CompatibilityRuleId('018e4e3a-3e7b-7b3e-8000-000000000051'),
                RuleType::REQUIRES,
                $this->frozenAttr,
                $this->refrigeratedAttr,
                RuleScope::LOCATION,
            ),
        ];
        $result = $this->checker->validate($hu, $this->activeLocation, $itemFamilyAttrs, $locationAttrs, $rules);
        self::assertTrue($result);
    }
}
