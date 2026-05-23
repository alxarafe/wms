<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\ValueObject;

/**
 * Enum representing the operational status of a storage location.
 */
enum LocationStatus: string
{
    case ACTIVE = 'ACTIVE';
    case BLOCKED = 'BLOCKED';
    case DISABLED = 'DISABLED';
}
