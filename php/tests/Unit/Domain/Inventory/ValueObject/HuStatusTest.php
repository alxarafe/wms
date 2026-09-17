<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory\ValueObject;

use Alxarafe\App\Domain\Inventory\ValueObject\HuStatus;
use PHPUnit\Framework\TestCase;

final class HuStatusTest extends TestCase
{
    public function testValues(): void
    {
        self::assertSame('AVAILABLE', HuStatus::AVAILABLE->value);
        self::assertSame('IN_TRANSIT', HuStatus::IN_TRANSIT->value);
        self::assertSame('BLOCKED', HuStatus::BLOCKED->value);
    }
}
