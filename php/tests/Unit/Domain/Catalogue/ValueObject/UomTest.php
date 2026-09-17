<?php
declare(strict_types=1);

namespace Tests\Unit\Domain\Catalogue\ValueObject;

use Alxarafe\App\Domain\Catalogue\ValueObject\Uom;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomId;
use PHPUnit\Framework\TestCase;

final class UomTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new UomId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $code = new UomCode('EA');
        $uom = new Uom($id, $code, 'Each');

        self::assertTrue($id->equals($uom->id()));
        self::assertTrue($code->equals($uom->code()));
        self::assertSame('Each', $uom->description());
    }

    public function testEqualsById(): void
    {
        $id = new UomId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $a = new Uom($id, new UomCode('EA'), 'Each');
        $b = new Uom($id, new UomCode('PC'), 'Piece');
        self::assertTrue($a->equals($b));
    }
}
