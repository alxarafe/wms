<?php

declare(strict_types=1);

namespace Tests\Integration\Persistence;

use Alxarafe\App\Domain\Model\Greeting;
use Alxarafe\App\Infrastructure\Persistence\PostgresGreetingRepository;
use Tests\Integration\DatabaseTestCase;

final class PostgresGreetingRepositoryTest extends DatabaseTestCase
{
    private PostgresGreetingRepository $repository;

    /**
     * @return list<string>
     */
    protected static function getMigrationFiles(): array
    {
        return [
            '001_create_greetings_table.sql',
        ];
    }

    protected function setUp(): void
    {
        parent::setUp();

        $this->repository = new PostgresGreetingRepository($this->getPdo());
    }

    public function testSaveAndFindById(): void
    {
        $greeting = new Greeting(
            'int-test-id',
            'Hello, Integration!',
            new \DateTimeImmutable('2026-06-15T10:00:00+00:00'),
        );

        $this->repository->save($greeting);

        $found = $this->repository->findById('int-test-id');
        self::assertNotNull($found);
        self::assertSame('Hello, Integration!', $found->getMessage());
        self::assertSame('int-test-id', $found->getId());
    }

    public function testFindAll(): void
    {
        $greeting = new Greeting(
            'int-test-all',
            'Hello, All!',
            new \DateTimeImmutable(),
        );

        $this->repository->save($greeting);

        $all = $this->repository->findAll();
        self::assertGreaterThanOrEqual(1, count($all));
    }
}
