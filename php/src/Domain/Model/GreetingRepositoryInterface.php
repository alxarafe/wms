<?php

declare(strict_types=1);

namespace Alxarafe\App\Domain\Model;

interface GreetingRepositoryInterface
{
    public function save(Greeting $greeting): void;

    public function findById(string $id): ?Greeting;

    /** @return Greeting[] */
    public function findAll(): array;
}
