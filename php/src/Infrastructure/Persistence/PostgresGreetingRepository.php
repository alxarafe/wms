<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Domain\Model\Greeting;
use Alxarafe\App\Domain\Model\GreetingRepositoryInterface;

final class PostgresGreetingRepository implements GreetingRepositoryInterface
{
    public function __construct(
        private readonly \PDO $pdo,
    ) {
    }

    public function save(Greeting $greeting): void
    {
        $stmt = $this->pdo->prepare(
            'INSERT INTO greetings (id, message, created_at) VALUES (:id, :message, :created_at)'
        );

        $stmt->execute([
            ':id' => $greeting->getId(),
            ':message' => $greeting->getMessage(),
            ':created_at' => $greeting->getCreatedAt()->format('c'),
        ]);
    }

    public function findById(string $id): ?Greeting
    {
        $stmt = $this->pdo->prepare('SELECT id, message, created_at FROM greetings WHERE id = :id');
        $stmt->execute([':id' => $id]);
        $row = $stmt->fetch(\PDO::FETCH_ASSOC);

        if ($row === false) {
            return null;
        }

        return $this->hydrate($row);
    }

    /** @return Greeting[] */
    public function findAll(): array
    {
        $stmt = $this->pdo->query('SELECT id, message, created_at FROM greetings ORDER BY created_at DESC');
        if ($stmt === false) {
            return [];
        }
        $rows = $stmt->fetchAll(\PDO::FETCH_ASSOC);

        return array_map(fn (array $row) => $this->hydrate($row), $rows);
    }

    /**
     * @param array<string, string> $row
     */
    private function hydrate(array $row): Greeting
    {
        return new Greeting(
            $row['id'],
            $row['message'],
            new \DateTimeImmutable($row['created_at']),
        );
    }
}
