<?php

declare(strict_types=1);

namespace Tests\Unit\Application\UseCase;

use Alxarafe\App\Application\UseCase\CreateGreetingUseCase;
use Alxarafe\App\Infrastructure\Persistence\InMemoryGreetingRepository;
use PHPUnit\Framework\TestCase;

final class CreateGreetingUseCaseTest extends TestCase
{
    public function testExecuteCreatesGreeting(): void
    {
        $repository = new InMemoryGreetingRepository();
        $useCase = new CreateGreetingUseCase($repository);

        $greeting = $useCase->execute('World');

        self::assertSame('Hello, World!', $greeting->getMessage());
        self::assertNotEmpty($greeting->getId());
        self::assertInstanceOf(\DateTimeImmutable::class, $greeting->getCreatedAt());
    }

    public function testExecutePersistsGreeting(): void
    {
        $repository = new InMemoryGreetingRepository();
        $useCase = new CreateGreetingUseCase($repository);

        $greeting = $useCase->execute('Test');

        $found = $repository->findById($greeting->getId());
        self::assertNotNull($found);
        self::assertSame('Hello, Test!', $found->getMessage());
    }
}
