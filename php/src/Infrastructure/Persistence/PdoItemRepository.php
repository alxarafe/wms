<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Catalogue\ItemRepository;
use Alxarafe\App\Application\Catalogue\ItemView;
use Alxarafe\App\Domain\Catalogue\Entity\Item;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemId;
use Alxarafe\App\Domain\Catalogue\ValueObject\Sku;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomId;
use Alxarafe\App\Infrastructure\Config\Database;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class PdoItemRepository implements ItemRepository
{
    private const SELECT_COLUMNS = <<<'SQL'
        i.id,
        i.sku,
        i.name,
        i.family_id,
        i.base_uom_id,
        i.is_batch_managed,
        i.is_expirable,
        f.code AS family_code,
        u.code AS uom_code
        SQL;

    private const ITEM = 'item';
    private const ITEM_FAMILY = 'item_family';
    private const UOM = 'uom';

    public function __construct(private PDO $pdo)
    {
    }

    public function findBySku(Sku $sku): ?Item
    {
        $sql = sprintf(
            'SELECT %s FROM %s i JOIN %s f ON f.id = i.family_id JOIN %s u ON u.id = i.base_uom_id WHERE i.sku = :sku',
            self::SELECT_COLUMNS,
            Database::qualified(self::ITEM),
            Database::qualified(self::ITEM_FAMILY),
            Database::qualified(self::UOM),
        );
        $row = $this->statement($sql, ['sku' => $sku->value()])->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $this->hydrate($row) : null;
    }

    /** @return list<ItemView> */
    public function findAll(): array
    {
        $sql = sprintf(
            'SELECT %s FROM %s i JOIN %s f ON f.id = i.family_id JOIN %s u ON u.id = i.base_uom_id ORDER BY i.sku',
            self::SELECT_COLUMNS,
            Database::qualified(self::ITEM),
            Database::qualified(self::ITEM_FAMILY),
            Database::qualified(self::UOM),
        );
        $rows = $this->statement($sql)->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_map(
            static fn (array $row): ItemView => new ItemView(
                self::hydrate($row),
                $row['family_code'],
                $row['uom_code'],
            ),
            $rows,
        ));
    }

    public function save(Item $item): void
    {
        $this->statement(
            sprintf(
                'INSERT INTO %s (id, sku, name, family_id, base_uom_id, is_batch_managed, is_expirable)
                 VALUES (:id, :sku, :name, :family_id, :base_uom_id, :is_batch_managed, :is_expirable)',
                Database::qualified(self::ITEM),
            ),
            [
                'id' => $item->id()->value(),
                'sku' => $item->sku()->value(),
                'name' => $item->name(),
                'family_id' => $item->familyId()->value(),
                'base_uom_id' => $item->baseUomId()->value(),
                'is_batch_managed' => $item->isBatchManaged() ? 1 : 0,
                'is_expirable' => $item->isExpirable() ? 1 : 0,
            ],
        );
    }

    /** @param array<string, string> $row */
    private static function hydrate(array $row): Item
    {
        return new Item(
            new ItemId($row['id']),
            new Sku($row['sku']),
            $row['name'],
            new ItemFamilyId($row['family_id']),
            new UomId($row['base_uom_id']),
            (bool) filter_var($row['is_batch_managed'], FILTER_VALIDATE_BOOLEAN),
            (bool) filter_var($row['is_expirable'], FILTER_VALIDATE_BOOLEAN),
        );
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
}
