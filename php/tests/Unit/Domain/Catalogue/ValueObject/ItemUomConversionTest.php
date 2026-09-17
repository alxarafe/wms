<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemUomConversion;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomId;
use InvalidArgumentException;
use PHPUnit\Framework\TestCase;

final class ItemUomConversionTest extends TestCase
{
    private UomId $fromUomId;
    private UomId $toUomId;

    protected function setUp(): void
    {
        $this->fromUomId = new UomId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $this->toUomId = new UomId('018e4e3a-3e7b-7b3e-8000-000000000002');
    }

    public function testCreateConversion(): void
    {
        $conversion = new ItemUomConversion($this->fromUomId, $this->toUomId, 2.0);
        self::assertTrue($this->fromUomId->equals($conversion->fromUomId()));
        self::assertTrue($this->toUomId->equals($conversion->toUomId()));
        self::assertSame(2.0, $conversion->factor());
    }

    public function testNegativeFactor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemUomConversion($this->fromUomId, $this->toUomId, -1.0);
    }

    public function testZeroFactor(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemUomConversion($this->fromUomId, $this->toUomId, 0.0);
    }

    public function testSameUom(): void
    {
        $this->expectException(InvalidArgumentException::class);
        new ItemUomConversion($this->fromUomId, $this->fromUomId, 1.0);
    }

    public function testEquals(): void
    {
        $a = new ItemUomConversion($this->fromUomId, $this->toUomId, 2.0);
        $b = new ItemUomConversion($this->fromUomId, $this->toUomId, 2.0);
        self::assertTrue($a->equals($b));
    }
}
