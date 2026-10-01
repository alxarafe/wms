<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Catalogue\ItemFamilyRepository;
use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
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

    public function __construct(private PDO $pdo)
    {
    }

    private function attributeTable(): string
    {
        return Database::catalogSchema() === 'public' ? 'attribute' : 'storage_attribute';
    }

    private function familyAttributeTable(): string
    {
        return Database::catalogSchema() === 'public' ? 'item_family_attribute' : 'family_storage_attribute';
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

    public function findById(ItemFamilyId $id): ?ItemFamily
    {
        $row = $this->fetch(
            sprintf('SELECT id, code, name FROM %s WHERE id = :id', Database::qualified(self::ITEM_FAMILY)),
            ['id' => $id->value()],
        );
        return $row === null ? null : $this->hydrate($row);
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
        $attrTable = $this->attributeTable();
        $linkTable = $this->familyAttributeTable();
        $linkColumn = $linkTable === 'item_family_attribute' ? 'item_family_id' : 'family_id';
        $codes = array_map(
            static fn (string $value): StorageAttributeCode => new StorageAttributeCode($value),
            array_values($this->statement(
                sprintf(
                    'SELECT a.code FROM %s a '
                    . 'JOIN %s ifa ON ifa.attribute_id = a.id '
                    . 'WHERE ifa.%s = :id ORDER BY a.code',
                    Database::qualified($attrTable),
                    Database::qualified($linkTable),
                    $linkColumn,
                ),
                ['id' => $row['id']],
            )->fetchAll(PDO::FETCH_COLUMN)),
        );
        return new ItemFamily(new ItemFamilyId($row['id']), new ItemFamilyCode($row['code']), $row['name'], null, $codes);
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

    public function existingAttributeCodes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }
        $attrTable = $this->attributeTable();
        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $statement = $this->pdo->prepare(sprintf(
            'SELECT code FROM %s WHERE code IN (%s) ORDER BY code' . ($this->pdo->inTransaction() ? ' FOR KEY SHARE' : ''),
            Database::qualified($attrTable),
            $placeholders,
        ));
        $statement->execute(array_map(static fn (StorageAttributeCode $code): string => $code->value(), $codes));
        return array_values($statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function save(ItemFamily $family): void
    {
        $attrTable = $this->attributeTable();
        $linkTable = $this->familyAttributeTable();
        $this->pdo->beginTransaction();
        try {
            $codes = $family->attributes();
            $attributeIdsByCode = [];
            if ($codes !== []) {
                $placeholders = implode(', ', array_fill(0, count($codes), '?'));
                $attributeStatement = $this->pdo->prepare(sprintf(
                    'SELECT id, code FROM %s WHERE code IN (%s) ORDER BY code FOR KEY SHARE',
                    Database::qualified($attrTable),
                    $placeholders,
                ));
                $attributeStatement->execute(array_map(static fn (StorageAttributeCode $code): string => $code->value(), $codes));
                foreach ($attributeStatement->fetchAll(PDO::FETCH_ASSOC) as $attribute) {
                    $attributeIdsByCode[$attribute['code']] = $attribute['id'];
                }
                if (count($attributeIdsByCode) !== count($codes)) {
                    throw new StorageAttributeNotFound('Storage attribute not found.');
                }
            }
            $statement = $this->pdo->prepare(sprintf(
                'INSERT INTO %s (id, code, name) VALUES (?, ?, ?)',
                Database::qualified(self::ITEM_FAMILY),
            ));
            $statement->execute([$family->id()->value(), $family->code()->value(), $family->name()]);
            $link = $this->pdo->prepare(sprintf(
                'INSERT INTO %s (family_id, attribute_id) VALUES (?, ?)',
                Database::qualified($linkTable),
            ));
            foreach ($family->attributes() as $attribute) {
                $link->execute([$family->id()->value(), $attributeIdsByCode[$attribute->value()]]);
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
