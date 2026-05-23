<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\ValueObject;

enum RuleType: string
{
    case REQUIRES = 'REQUIRES';
    case FORBIDS = 'FORBIDS';
}
