<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Catalogue\ItemFamilyRepository;
use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;
use Alxarafe\App\Application\Catalogue\StorageAttributeNotFound;
use Alxarafe\App\Application\Catalogue\ItemFamilyConflict;
use PDOException;
use Alxarafe\App\Infrastructure\Config\Database;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

final readonly class PdoItemFamilyRepository implements ItemFamilyRepository
{
    private const ITEM_FAMILY = 'item_family';
    private const STORAGE_ATTRIBUTE = 'storage_attribute';
    private const FAMILY_STORAGE_ATTRIBUTE = 'family_storage_attribute';

    public function __construct(private PDO $pdo)
    {
    }

    public function findByCode(ItemFamilyCode $code): ?ItemFamily
    {
        $row = $this->fetch(
            sprintf('SELECT id, code, name FROM %s WHERE code = :code', Database::qualified(self::ITEM_FAMILY)),
            ['code' => $code->value()],
        );
        return $row === null ? null : $this->hydrate($row);
    }

    /** @return list<ItemFamily> */
    public function findAll(): array
    {
        $rows = $this->statement(sprintf('SELECT id, code, name FROM %s ORDER BY code', Database::qualified(self::ITEM_FAMILY)))->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_map(fn (array $row): ItemFamily => $this->hydrate($row), $rows));
    }

    /** @param array<string, scalar> $params
     *  @return array<string, string>|null
     */
    private function fetch(string $sql, array $params): ?array
    {
        $statement = $this->statement($sql, $params)->fetch(PDO::FETCH_ASSOC);
        return is_array($statement) ? $statement : null;
    }

    /** @param array<string, string> $row */
    private function hydrate(array $row): ItemFamily
    {
        $codes = array_map(
            static fn (string $value): StorageAttributeId => new StorageAttributeId($value),
            array_values($this->statement(
                sprintf(
                    'SELECT sa.id FROM %s sa '
                    . 'JOIN %s fsa ON fsa.attribute_id = sa.id '
                    . 'WHERE fsa.family_id = :id ORDER BY sa.id',
                    Database::qualified(self::STORAGE_ATTRIBUTE),
                    Database::qualified(self::FAMILY_STORAGE_ATTRIBUTE),
                ),
                ['id' => $row['id']],
            )->fetchAll(PDO::FETCH_COLUMN)),
        );
        return new ItemFamily(new ItemFamilyId($row['id']), new ItemFamilyCode($row['code']), $row['name'], $codes);
    }

    /** @param array<string, scalar> $params */
    private function statement(string $sql, array $params = []): PDOStatement
    {
        $statement = $this->pdo->prepare($sql);
        if ($statement === false) {
            throw new RuntimeException('Unable to prepare statement.');
        }
        $statement->execute($params);
        return $statement;
    }

    public function existingAttributeIds(array $codes): array
    {
        if ($codes === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $statement = $this->pdo->prepare(sprintf(
            'SELECT id FROM %s WHERE id IN (%s) ORDER BY id' . ($this->pdo->inTransaction() ? ' FOR KEY SHARE' : ''),
            Database::qualified(self::STORAGE_ATTRIBUTE),
            $placeholders,
        ));
        $statement->execute(array_map(static fn (StorageAttributeId $code): string => $code->value(), $codes));
        return array_values($statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function save(ItemFamily $family): void
    {
        $this->pdo->beginTransaction();
        try {
            $ids = array_map(static fn (StorageAttributeId $id): string => $id->value(), $family->attributes());
            if (array_diff($ids, $this->existingAttributeIds($family->attributes())) !== []) {
                throw new StorageAttributeNotFound('Storage attribute not found.');
            }
            $statement = $this->pdo->prepare(sprintf(
                'INSERT INTO %s (id, code, name) VALUES (?, ?, ?)',
                Database::qualified(self::ITEM_FAMILY),
            ));
            $statement->execute([$family->id()->value(), $family->code()->value(), $family->name()]);
            $link = $this->pdo->prepare(sprintf(
                'INSERT INTO %s (family_id, attribute_id) VALUES (?, ?)',
                Database::qualified(self::FAMILY_STORAGE_ATTRIBUTE),
            ));
            foreach ($family->attributes() as $attribute) {
                $link->execute([$family->id()->value(), $attribute->value()]);
            }
            $this->pdo->commit();
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            if ($error instanceof PDOException && $error->getCode() === '23505') {
                throw new ItemFamilyConflict('Item family code already exists.', 0, $error);
            }
            if ($error instanceof PDOException && $error->getCode() === '23503') {
                throw new StorageAttributeNotFound('Storage attribute not found.', 0, $error);
            }
            throw $error;
        }
    }
}
