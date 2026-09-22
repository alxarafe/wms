<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Persistence;

use Alxarafe\App\Application\Catalogue\UomRepository;
use Alxarafe\App\Domain\Catalogue\ValueObject\Uom;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\UomId;
use Alxarafe\App\Infrastructure\Config\Database;
use PDO;
use PDOStatement;
use RuntimeException;

final readonly class PdoUomRepository implements UomRepository
{
    public function __construct(private PDO $pdo)
    {
    }

    public function findByCode(UomCode $code): ?Uom
    {
        $row = $this->statement(sprintf('SELECT id, code, description FROM %s WHERE code = :code', Database::qualified('uom')), ['code' => $code->value()])
            ->fetch(PDO::FETCH_ASSOC);
        return is_array($row) ? $this->hydrate($row) : null;
    }

    /** @return list<Uom> */
    public function findAll(): array
    {
        $rows = $this->statement(sprintf('SELECT id, code, description FROM %s ORDER BY code', Database::qualified('uom')))->fetchAll(PDO::FETCH_ASSOC);
        return array_values(array_map(fn (array $row): Uom => $this->hydrate($row), $rows));
    }

    public function save(Uom $uom): void
    {
        $this->statement(
            sprintf('INSERT INTO %s (id, code, description) VALUES (:id, :code, :description)', Database::qualified('uom')),
            ['id' => $uom->id()->value(), 'code' => $uom->code()->value(), 'description' => $uom->description()],
        );
    }

    /** @param array<string, string> $row */
    private function hydrate(array $row): Uom
    {
        return new Uom(new UomId($row['id']), new UomCode($row['code']), $row['description']);
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
