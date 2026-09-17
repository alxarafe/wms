<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\ValueObject;

use Alxarafe\App\Domain\Rules\ValueObject\TargetType;
use PHPUnit\Framework\TestCase;

final class TargetTypeTest extends TestCase
{
    public function testValues(): void
    {
        self::assertSame('LOCATION', TargetType::LOCATION->value);
        self::assertSame('FAMILY', TargetType::FAMILY->value);
    }
}
