<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\ValueObject;

use Alxarafe\App\Domain\Rules\ValueObject\MovementType;
use PHPUnit\Framework\TestCase;

final class MovementTypeTest extends TestCase
{
    public function testValues(): void
    {
        self::assertSame('INBOUND', MovementType::INBOUND->value);
        self::assertSame('OUTBOUND', MovementType::OUTBOUND->value);
        self::assertSame('TRANSFER', MovementType::TRANSFER->value);
        self::assertSame('ADJUSTMENT', MovementType::ADJUSTMENT->value);
    }
}
