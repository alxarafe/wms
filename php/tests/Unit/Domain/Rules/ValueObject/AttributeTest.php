<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Rules\ValueObject;

use Alxarafe\App\Domain\Rules\ValueObject\Attribute;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeCode;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeId;
use Alxarafe\App\Domain\Rules\ValueObject\TargetType;
use PHPUnit\Framework\TestCase;

final class AttributeTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $code = new AttributeCode('COLD');
        $attr = new Attribute($id, $code, TargetType::FAMILY);

        self::assertTrue($id->equals($attr->id()));
        self::assertTrue($code->equals($attr->code()));
        self::assertSame(TargetType::FAMILY, $attr->targetType());
    }

    public function testEquals(): void
    {
        $id = new AttributeId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $a = new Attribute($id, new AttributeCode('COLD'), TargetType::FAMILY);
        $b = new Attribute($id, new AttributeCode('OTHER'), TargetType::LOCATION);
        self::assertTrue($a->equals($b));
    }
}
