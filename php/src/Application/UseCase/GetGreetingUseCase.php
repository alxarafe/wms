<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\UseCase;

use Alxarafe\App\Domain\Model\Greeting;
use Alxarafe\App\Domain\Model\GreetingRepositoryInterface;

final readonly class GetGreetingUseCase
{
    public function __construct(
        private GreetingRepositoryInterface $repository,
    ) {
    }

    public function execute(string $id): ?Greeting
    {
        return $this->repository->findById($id);
    }

    /**
     * @return Greeting[]
     */
    public function findAll(): array
    {
        return $this->repository->findAll();
    }
}
