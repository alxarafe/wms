<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Rules\ValueObject;

enum MovementType: string
{
    case INBOUND = 'INBOUND';
    case OUTBOUND = 'OUTBOUND';
    case TRANSFER = 'TRANSFER';
    case ADJUSTMENT = 'ADJUSTMENT';
}
