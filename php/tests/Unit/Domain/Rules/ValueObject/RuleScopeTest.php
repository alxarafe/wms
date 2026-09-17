<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\ValueObject;

use Alxarafe\App\Domain\Rules\ValueObject\RuleScope;
use PHPUnit\Framework\TestCase;

final class RuleScopeTest extends TestCase
{
    public function testValues(): void
    {
        self::assertSame('LOCATION', RuleScope::LOCATION->value);
        self::assertSame('AISLE', RuleScope::AISLE->value);
        self::assertSame('ZONE', RuleScope::ZONE->value);
    }
}
