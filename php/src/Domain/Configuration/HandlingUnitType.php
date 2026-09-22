<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Configuration;

use Alxarafe\App\Domain\Shared\ValueObject\Uuid;

final readonly class HandlingUnitType
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public bool $isActive = true,
    ) {
        Uuid::validate($id);
        ConfigurationText::validate($code, 30, 'code');
        ConfigurationText::validate($name, 255, 'name');
    }
}
