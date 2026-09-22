<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Configuration;

use Alxarafe\App\Domain\Shared\ValueObject\Uuid;

/** Configuración v2; la topología operativa antigua sigue en public. */
final readonly class WarehouseConfiguration
{
    public function __construct(
        public string $id,
        public string $code,
        public string $name,
        public WarehouseFormat $format,
    ) {
        Uuid::validate($id);
        ConfigurationText::validate($code, 10, 'code');
        ConfigurationText::validate($name, 255, 'name');
    }
}
