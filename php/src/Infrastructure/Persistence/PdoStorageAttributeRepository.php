<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Catalogue\StorageAttributeConflict;
use Alxarafe\App\Application\Catalogue\StorageAttributeRepository;
use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;
use Alxarafe\App\Infrastructure\Config\Database;
use PDO;
use PDOException;

final readonly class PdoStorageAttributeRepository implements StorageAttributeRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function find(StorageAttributeId $id): ?StorageAttribute
    {
        return $this->one('id', $id->value());
    }

    public function findByCode(StorageAttributeCode $code): ?StorageAttribute
    {
        return $this->one('code', $code->value());
    }

    private function one(string $column, string $value): ?StorageAttribute
    {
        $statement = $this->pdo->prepare('SELECT id, code, name, exclusive_group_code FROM '
            . Database::qualified('storage_attribute') . ' WHERE ' . $column . ' = ?');
        $statement->execute([$value]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $this->hydrate($row) : null;
    }

    public function findAll(): array
    {
        $statement = $this->pdo->prepare('SELECT id, code, name, exclusive_group_code FROM '
            . Database::qualified('storage_attribute') . ' ORDER BY code');
        $statement->execute();
        return array_map($this->hydrate(...), $statement->fetchAll(PDO::FETCH_ASSOC));
    }

    /** @param array<string, string|null> $row */
    private function hydrate(array $row): StorageAttribute
    {
        return new StorageAttribute(
            new StorageAttributeId((string) $row['id']),
            new StorageAttributeCode((string) $row['code']),
            (string) $row['name'],
            $row['exclusive_group_code'],
        );
    }

    public function save(StorageAttribute $attribute): void
    {
        try {
            $statement = $this->pdo->prepare('INSERT INTO ' . Database::qualified('storage_attribute')
                . ' (id, code, name, exclusive_group_code) VALUES (?, ?, ?, ?)');
            $statement->execute([
                $attribute->id()->value(), $attribute->code()->value(), $attribute->name(), $attribute->exclusiveGroupCode(),
            ]);
        } catch (PDOException $error) {
            if ($error->getCode() === '23505') {
                throw new StorageAttributeConflict('Storage attribute code already exists.', 0, $error);
            }
            throw $error;
        }
    }
}
