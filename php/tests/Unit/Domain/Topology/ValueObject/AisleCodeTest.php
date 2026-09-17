<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Topology\ValueObject;

use Alxarafe\App\Domain\Topology\ValueObject\AisleCode;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class AisleCodeTest extends TestCase
{
    public function testCreate(): void
    {
        $code = new AisleCode('A-01');
        self::assertSame('A-01', $code->value());
    }

    public function testEmpty(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AisleCode('');
    }

    public function testTooLong(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new AisleCode(str_repeat('A', 21));
    }

    public function testEquals(): void
    {
        $a = new AisleCode('A01');
        $b = new AisleCode('A01');
        self::assertTrue($a->equals($b));
    }

    public function testToString(): void
    {
        $code = new AisleCode('MAIN');
        self::assertSame('MAIN', (string) $code);
    }
}
