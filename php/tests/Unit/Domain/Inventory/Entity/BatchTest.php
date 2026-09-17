<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Inventory\Entity;

use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Inventory\Entity\Batch;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchCode;
use Alxarafe\App\Domain\Inventory\ValueObject\BatchId;
use DateTimeImmutable;
use PHPUnit\Framework\TestCase;

final class BatchTest extends TestCase
{
    public function testCreate(): void
    {
        $id = new BatchId('018e4e3a-3e7b-7b3e-8000-000000000001');
        $itemId = new ItemId('018e4e3a-3e7b-7b3e-8000-000000000002');
        $code = new BatchCode('BATCH-001');
        $expiration = new DateTimeImmutable('2027-01-01');
        $batch = new Batch($id, $itemId, $code, $expiration);

        self::assertTrue($id->equals($batch->id()));
        self::assertTrue($itemId->equals($batch->itemId()));
        self::assertTrue($code->equals($batch->batchCode()));
        self::assertSame($expiration, $batch->expirationDate());
    }

    public function testCreateWithoutExpiration(): void
    {
        $batch = new Batch(
            new BatchId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new ItemId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new BatchCode('BATCH-001'),
            null,
        );
        self::assertNull($batch->expirationDate());
    }

    public function testIsExpired(): void
    {
        $batch = new Batch(
            new BatchId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new ItemId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new BatchCode('BATCH-001'),
            new DateTimeImmutable('2025-01-01'),
        );
        self::assertTrue($batch->isExpired(new DateTimeImmutable('2026-01-01')));
    }

    public function testIsNotExpired(): void
    {
        $batch = new Batch(
            new BatchId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new ItemId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new BatchCode('BATCH-001'),
            new DateTimeImmutable('2027-01-01'),
        );
        self::assertFalse($batch->isExpired(new DateTimeImmutable('2026-01-01')));
    }

    public function testNoExpirationNeverExpired(): void
    {
        $batch = new Batch(
            new BatchId('018e4e3a-3e7b-7b3e-8000-000000000001'),
            new ItemId('018e4e3a-3e7b-7b3e-8000-000000000002'),
            new BatchCode('BATCH-001'),
            null,
        );
        self::assertFalse($batch->isExpired(new DateTimeImmutable('2030-01-01')));
    }
}
