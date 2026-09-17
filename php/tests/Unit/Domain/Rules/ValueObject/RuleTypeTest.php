<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\ValueObject;

use Alxarafe\App\Domain\Rules\ValueObject\RuleType;
use PHPUnit\Framework\TestCase;

final class RuleTypeTest extends TestCase
{
    public function testValues(): void
    {
        self::assertSame('REQUIRES', RuleType::REQUIRES->value);
        self::assertSame('FORBIDS', RuleType::FORBIDS->value);
    }
}
