<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\ValueObject;

enum TargetType: string
{
    case LOCATION = 'LOCATION';
    case FAMILY = 'FAMILY';
}
