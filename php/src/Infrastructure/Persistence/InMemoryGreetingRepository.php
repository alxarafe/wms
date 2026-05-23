<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Domain\Model\Greeting;
use Alxarafe\App\Domain\Model\GreetingRepositoryInterface;

final class InMemoryGreetingRepository implements GreetingRepositoryInterface
{
    /** @var array<string, Greeting> */
    private array $items = [];

    public function save(Greeting $greeting): void
    {
        $this->items[$greeting->getId()] = $greeting;
    }

    public function findById(string $id): ?Greeting
    {
        return $this->items[$id] ?? null;
    }

    /** @return Greeting[] */
    public function findAll(): array
    {
        return array_values($this->items);
    }
}
