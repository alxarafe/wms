<?php

declare(strict_types=1);

namespace Alxarafe\App\Application\UseCase;

use Alxarafe\App\Domain\Model\Greeting;
use Alxarafe\App\Domain\Model\GreetingRepositoryInterface;

final readonly class CreateGreetingUseCase
{
    public function __construct(
        private GreetingRepositoryInterface $repository,
    ) {
    }

    public function execute(string $name): Greeting
    {
        $id = bin2hex(random_bytes(16));
        $message = sprintf('Hello, %s!', $name);
        $createdAt = new \DateTimeImmutable();

        $greeting = new Greeting($id, $message, $createdAt);

        $this->repository->save($greeting);

        return $greeting;
    }
}
