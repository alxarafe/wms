<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\LocationRole;
use PHPUnit\Framework\TestCase;

final class LocationRoleTest extends TestCase
{
    public function testValues(): void
    {
        self::assertSame('PICKING', LocationRole::PICKING->value);
        self::assertSame('RESERVE', LocationRole::RESERVE->value);
    }
}
