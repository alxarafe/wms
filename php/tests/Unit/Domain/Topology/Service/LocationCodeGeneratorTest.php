<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\Service;

use Alxarafe\App\Domain\Topology\Service\LocationCodeGenerator;
use Alxarafe\App\Domain\Topology\ValueObject\NamingPolicy;
use PHPUnit\Framework\TestCase;

final class LocationCodeGeneratorTest extends TestCase
{
    private LocationCodeGenerator $generator;

    protected function setUp(): void
    {
        $this->generator = new LocationCodeGenerator();
    }

    public function testGenerateWithDashSeparator(): void
    {
        $policy = new NamingPolicy('-', 2, 2, 2, 3, 2);
        $code = $this->generator->generate($policy, 'WH', 'Z', 'A', 1, 1);
        self::assertSame('WH-0Z-0A-001-01', $code->value());
    }

    public function testGenerateWithDotSeparator(): void
    {
        $policy = new NamingPolicy('.', 3, 2, 3, 2, 1);
        $code = $this->generator->generate($policy, 'WH', 'Z', 'A', 1, 1);
        self::assertSame('0WH.0Z.00A.01.1', $code->value());
    }

    public function testGenerateWithLargeNumbers(): void
    {
        $policy = new NamingPolicy('-', 2, 2, 2, 3, 2);
        $code = $this->generator->generate($policy, 'WH', 'Z', 'A', 999, 99);
        self::assertSame('WH-0Z-0A-999-99', $code->value());
    }

    public function testGenerateWithNoSeparator(): void
    {
        $policy = new NamingPolicy('_', 1, 1, 1, 1, 1);
        $code = $this->generator->generate($policy, 'W', 'Z', 'A', 1, 1);
        self::assertSame('W_Z_A_1_1', $code->value());
    }
}
