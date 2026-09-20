<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Catalogue\ItemFamilyRepository;
use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeCode;
use PDO;
use PDOStatement;
use RuntimeException;
use Throwable;

final readonly class PdoItemFamilyRepository implements ItemFamilyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByCode(ItemFamilyCode $code): ?ItemFamily
    {
        $row = $this->fetch(
            'SELECT id, code, name FROM item_family WHERE code = :code',
            ['code' => $code->value()],
        );
        return $row === null ? null : $this->hydrate($row);
    }

    /** @return list<ItemFamily> */
    public function findAll(): array
    {
        $rows = $this->statement('SELECT id, code, name FROM item_family ORDER BY code')->fetchAll(PDO::FETCH_ASSOC);
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
            static fn (string $value): AttributeCode => new AttributeCode($value),
            array_values($this->statement(
                'SELECT a.code FROM attribute a JOIN item_family_attribute fa ON fa.attribute_id = a.id '
                . 'WHERE fa.item_family_id = :id ORDER BY a.code',
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

    public function availableFamilyAttributes(array $codes): array
    {
        if ($codes === []) {
            return [];
        }
        $placeholders = implode(', ', array_fill(0, count($codes), '?'));
        $statement = $this->pdo->prepare(
            "SELECT code FROM attribute WHERE target_type = 'FAMILY' AND code IN ($placeholders)"
        );
        $statement->execute(array_map(static fn (AttributeCode $code): string => $code->value(), $codes));
        return array_values($statement->fetchAll(PDO::FETCH_COLUMN));
    }

    public function save(ItemFamily $family): void
    {
        $this->pdo->beginTransaction();
        try {
            $statement = $this->pdo->prepare('INSERT INTO item_family (id, code, name) VALUES (?, ?, ?)');
            $statement->execute([$family->id()->value(), $family->code()->value(), $family->name()]);
            $link = $this->pdo->prepare(
                "INSERT INTO item_family_attribute (item_family_id, attribute_id) "
                . "SELECT ?, id FROM attribute WHERE code = ? AND target_type = 'FAMILY'"
            );
            foreach ($family->attributes() as $attribute) {
                $link->execute([$family->id()->value(), $attribute->value()]);
            }
            $this->pdo->commit();
        } catch (Throwable $error) {
            $this->pdo->rollBack();
            throw $error;
        }
    }
}
