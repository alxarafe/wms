<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Controller;

use Alxarafe\App\Application\UseCase\CreateGreetingUseCase;
use Alxarafe\App\Application\UseCase\GetGreetingUseCase;
use flight\Engine;

final class GreetingController
{
    public function __construct(
        private CreateGreetingUseCase $createUseCase,
        private GetGreetingUseCase $getUseCase,
    ) {
    }

    /** @phpstan-ignore-next-line */
    public function greet(Engine $app): void
    {
        $name = $app->request()->query->name ?? 'World';

        $greeting = $this->createUseCase->execute($name);

        $app->json([
            'id' => $greeting->getId(),
            'message' => $greeting->getMessage(),
            'createdAt' => $greeting->getCreatedAt()->format('c'),
        ]);
    }

    /** @phpstan-ignore-next-line */
    public function getAll(Engine $app): void
    {
        $greetings = $this->getUseCase->findAll();

        $data = array_map(fn ($g) => [
            'id' => $g->getId(),
            'message' => $g->getMessage(),
            'createdAt' => $g->getCreatedAt()->format('c'),
        ], $greetings);

        $app->json($data);
    }
}
