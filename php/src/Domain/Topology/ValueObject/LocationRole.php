<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Topology\ValueObject;

/**
 * Enum representing the role of a storage location.
 */
enum LocationRole: string
{
    case PICKING = 'PICKING';
    case RESERVE = 'RESERVE';
}
