<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Catalogue\ItemFamilyRepository;
use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeCode;
use PDO;
use Throwable;

final readonly class PdoItemFamilyRepository implements ItemFamilyRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByCode(ItemFamilyCode $code): ?ItemFamily
    {
        $statement = $this->pdo->prepare('SELECT id, code, name FROM item_family WHERE code = :code');
        $statement->execute(['code' => $code->value()]);
        $row = $statement->fetch(PDO::FETCH_ASSOC);
        if (!is_array($row)) {
            return null;
        }

        $attributes = $this->pdo->prepare(
            'SELECT a.code FROM attribute a JOIN item_family_attribute fa ON fa.attribute_id = a.id '
            . 'WHERE fa.item_family_id = :id ORDER BY a.code'
        );
        $attributes->execute(['id' => $row['id']]);
        $codes = array_map(
            static fn (string $value): AttributeCode => new AttributeCode($value),
            array_values($attributes->fetchAll(PDO::FETCH_COLUMN)),
        );
        return new ItemFamily(new ItemFamilyId($row['id']), new ItemFamilyCode($row['code']), $row['name'], $codes);
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
