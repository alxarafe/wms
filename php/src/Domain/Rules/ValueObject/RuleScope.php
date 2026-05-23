<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\ValueObject;

enum RuleScope: string
{
    case LOCATION = 'LOCATION';
    case AISLE = 'AISLE';
    case ZONE = 'ZONE';
}
