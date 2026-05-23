<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Inventory\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchCode;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchId;
use DateTimeImmutable;
use InvalidArgumentException;

/**
 * Entity representing a specific manufactured/received batch of merchandise.
 */
final readonly class Batch
{
    public function __construct(
        private BatchId $id,
        private ItemId $itemId,
        private BatchCode $batchCode,
        private ?DateTimeImmutable $expirationDate,
    ) {
    }

    public function id(): BatchId
    {
        return $this->id;
    }

    public function itemId(): ItemId
    {
        return $this->itemId;
    }

    public function batchCode(): BatchCode
    {
        return $this->batchCode;
    }

    public function expirationDate(): ?DateTimeImmutable
    {
        return $this->expirationDate;
    }

    public function isExpired(DateTimeImmutable $now): bool
    {
        if ($this->expirationDate === null) {
            return false;
        }
        return $this->expirationDate < $now;
    }
}
