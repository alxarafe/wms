<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\Movement;

use RuntimeException;

/**
 * Error de operación de stock con su estado HTTP asociado, para que el
 * adaptador HTTP lo traduzca directamente (404 desconocido, 400 semántico
 * de entrada, 409 conflicto de estado del almacén).
 */
final class StockOperationException extends RuntimeException
{
    public function __construct(string $message, private int $status = 409)
    {
        parent::__construct($message);
    }

    public function status(): int
    {
        return $this->status;
    }
}
