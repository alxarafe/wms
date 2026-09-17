<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\NamingPolicy;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class NamingPolicyTest extends TestCase
{
    public function testCreate(): void
    {
        $policy = new NamingPolicy('-', 2, 3, 2, 3, 2);
        self::assertSame('-', $policy->codeSeparator());
        self::assertSame(2, $policy->warehousePadding());
        self::assertSame(3, $policy->zonePadding());
        self::assertSame(2, $policy->aislePadding());
        self::assertSame(3, $policy->bayPadding());
        self::assertSame(2, $policy->levelPadding());
    }

    public function testEmptySeparator(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NamingPolicy('', 2, 3, 2, 3, 2);
    }

    public function testSeparatorTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NamingPolicy('----', 2, 3, 2, 3, 2);
    }

    public function testPaddingTooLow(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NamingPolicy('-', 0, 3, 2, 3, 2);
    }

    public function testPaddingTooHigh(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new NamingPolicy('-', 11, 3, 2, 3, 2);
    }

    public function testEquals(): void
    {
        $a = new NamingPolicy('-', 2, 3, 2, 3, 2);
        $b = new NamingPolicy('-', 2, 3, 2, 3, 2);
        $c = new NamingPolicy('.', 2, 3, 2, 3, 2);
        self::assertTrue($a->equals($b));
        self::assertFalse($a->equals($c));
    }
}
