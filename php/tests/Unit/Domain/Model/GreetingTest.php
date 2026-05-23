<?php

declare(strict_types=1);

namespace Tests\Unit\Domain\Model;

use Alxarafe\App\Domain\Model\Greeting;
use PHPUnit\Framework\TestCase;

final class GreetingTest extends TestCase
{
    public function testCreateGreeting(): void
    {
        $id = 'test-id';
        $message = 'Hello, World!';
        $createdAt = new \DateTimeImmutable('2026-01-01T00:00:00+00:00');

        $greeting = new Greeting($id, $message, $createdAt);

        self::assertSame($id, $greeting->getId());
        self::assertSame($message, $greeting->getMessage());
        self::assertSame($createdAt, $greeting->getCreatedAt());
    }

    public function testGreetingIsImmutable(): void
    {
        $greeting = new Greeting('id', 'msg', new \DateTimeImmutable());

        $this->expectException(\Error::class);
        /** @phpstan-ignore-next-line */
        $greeting->id = 'new-id';
    }
}
