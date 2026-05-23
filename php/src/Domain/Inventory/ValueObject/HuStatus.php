<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\ValueObject;

enum HuStatus: string
{
    case AVAILABLE = 'AVAILABLE';
    case IN_TRANSIT = 'IN_TRANSIT';
    case BLOCKED = 'BLOCKED';
}
