<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\LocationStatus;
use PHPUnit\Framework\TestCase;

final class LocationStatusTest extends TestCase
{
    public function testValues(): void
    {
        self::assertSame('ACTIVE', LocationStatus::ACTIVE->value);
        self::assertSame('BLOCKED', LocationStatus::BLOCKED->value);
        self::assertSame('DISABLED', LocationStatus::DISABLED->value);
    }
}
