<?php

declare(strict_types=1);

namespace Tests\Integration\Catalogue;

use Alxarafe\App\Application\Catalogue\CreateItemFamily;
use Alxarafe\App\Application\Catalogue\CreateStorageAttribute;
use Alxarafe\App\Application\Catalogue\ItemFamilyConflict;
use Alxarafe\App\Application\Catalogue\StorageAttributeConflict;
use Alxarafe\App\Application\Catalogue\StorageAttributeNotFound;
use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\ItemFamilyId;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeId;
use Alxarafe\App\Domain\Shared\ValueObject\Uuid;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoItemFamilyRepository;
use Alxarafe\App\Infrastructure\Persistence\PdoStorageAttributeRepository;
use PDO;
use PDOException;
use PHPUnit\Framework\TestCase;

/** Solo bin/php_catalogue_test.sh puede habilitar estas pruebas destructivas. */
final class StorageAttributePersistenceTest extends TestCase
{
    private PDO $pdo;
    private PdoItemFamilyRepository $families;
    private PdoStorageAttributeRepository $attributes;

    protected function setUp(): void
    {
        if (getenv('PHP_CATALOGUE_TEST') !== '1') {
            self::markTestSkipped('Use bin/php_catalogue_test.sh with dedicated test databases.');
        }
        $this->pdo = Database::getConnection();
        $statement = $this->pdo->query('SELECT current_database()');
        self::assertInstanceOf(\PDOStatement::class, $statement);
        self::assertContains($statement->fetchColumn(), ['wms_php_catalogue_a_test', 'wms_php_catalogue_b_test']);
        $this->pdo->exec('TRUNCATE wms_review_v2.family_storage_attribute, wms_review_v2.item_family, wms_review_v2.storage_attribute CASCADE');
        $this->families = new PdoItemFamilyRepository($this->pdo);
        $this->attributes = new PdoStorageAttributeRepository($this->pdo);
    }

    protected function tearDown(): void
    {
        if (isset($this->pdo) && $this->pdo->inTransaction()) {
            $this->pdo->rollBack();
        }
    }

    public function testRoundTripUsesExistingAttributeIds(): void
    {
        $food = $this->attribute('food');
        $chilled = (new CreateStorageAttribute($this->attributes))->execute('chilled', 'Refrigerado', 'thermal');
        self::assertEquals($food, $this->attributes->find($food->id()));
        self::assertEquals($chilled, $this->attributes->findByCode(new StorageAttributeCode('CHILLED')));
        self::assertSame('THERMAL', $this->attributes->find($chilled->id())?->exclusiveGroupCode());
        self::assertSame(['CHILLED', 'FOOD'], array_map(static fn (StorageAttribute $a): string => $a->code()->value(), $this->attributes->findAll()));
        $family = (new CreateItemFamily($this->families))->execute('ALIMENTOS', 'Alimentos', [$food->code()->value(), $chilled->code()->value()]);
        $stored = $this->families->findByCode($family->code());
        self::assertNotNull($stored);
        self::assertSame($family->id()->value(), $stored->id()->value());
        $ids = array_map(static fn (StorageAttributeId $id): string => $id->value(), $stored->attributes());
        self::assertEqualsCanonicalizing([$food->code()->value(), $chilled->code()->value()], $ids);
        self::assertSame(2, $this->countRows('family_storage_attribute'));
        self::assertNull($food->exclusiveGroupCode());
        self::assertFalse($this->pdo->inTransaction());
    }

    public function testDatabaseDuplicateIsTranslatedEvenWithoutPrecheck(): void
    {
        $food = $this->attribute('FOOD');
        try {
            $this->attributes->save(new StorageAttribute(new StorageAttributeId(Uuid::generate()), $food->code(), 'Duplicado'));
            self::fail('Unique constraint must be enforced.');
        } catch (StorageAttributeConflict) {
            self::assertSame(1, $this->countRows('storage_attribute'));
        }
        (new CreateItemFamily($this->families))->execute('ALIMENTOS', 'Alimentos', []);
        try {
            $this->families->save(new ItemFamily(ItemFamilyId::generate(), new ItemFamilyCode('ALIMENTOS'), 'Duplicada'));
            self::fail('Duplicate family must fail.');
        } catch (ItemFamilyConflict) {
            self::assertSame(1, $this->countRows('item_family'));
            self::assertFalse($this->pdo->inTransaction());
        }
    }

    public function testMissingLastReferenceDoesNotLeavePartialFamily(): void
    {
        $food = $this->attribute('FOOD');
        try {
            (new CreateItemFamily($this->families))->execute('REJECTED', 'Rechazada', [$food->code()->value(), 'MISSING']);
            self::fail('Missing reference must fail.');
        } catch (StorageAttributeNotFound) {
            self::assertSame(0, $this->countRows('item_family'));
            self::assertSame(0, $this->countRows('family_storage_attribute'));
            self::assertSame(1, $this->countRows('storage_attribute'));
        }
    }

    public function testRepositoryRechecksReferencesBeforeInsert(): void
    {
        $missing = new StorageAttributeCode('MISSING');
        try {
            $this->families->save(new ItemFamily(ItemFamilyId::generate(), new ItemFamilyCode('REJECTED'), 'Rechazada', [$missing]));
            self::fail('Repository must recheck references inside its transaction.');
        } catch (StorageAttributeNotFound) {
            self::assertFalse($this->pdo->inTransaction());
            self::assertSame(0, $this->countRows('item_family'));
        }
    }

    public function testFailureOnSecondLinkRollsBackFamilyAndFirstLink(): void
    {
        $food = $this->attribute('FOOD');
        $frozen = $this->attribute('FROZEN');
        // Un fallo técnico real DESPUÉS del INSERT de familia y de su primer vínculo.
        $this->pdo->exec("CREATE FUNCTION pg_temp.reject_frozen_link() RETURNS trigger LANGUAGE plpgsql AS \$\$
            BEGIN IF NEW.attribute_id = '" . $frozen->id()->value() . "'::uuid THEN
                RAISE EXCEPTION 'Injected link failure' USING ERRCODE = 'WMS02';
            END IF; RETURN NEW; END; \$\$");
        $this->pdo->exec('CREATE TRIGGER reject_frozen_link BEFORE INSERT ON wms_review_v2.family_storage_attribute
            FOR EACH ROW EXECUTE FUNCTION pg_temp.reject_frozen_link()');
        try {
            try {
                (new CreateItemFamily($this->families))->execute('ROLLBACK', 'Rollback', [$food->id()->value(), $frozen->id()->value()]);
                self::fail('Injected failure must abort the transaction.');
            } catch (PDOException $error) {
                self::assertSame('WMS02', $error->getCode());
                self::assertFalse($this->pdo->inTransaction());
                self::assertSame(0, $this->countRows('item_family'));
                self::assertSame(0, $this->countRows('family_storage_attribute'));
                self::assertSame(2, $this->countRows('storage_attribute'));
            }
        } finally {
            $this->pdo->exec('DROP TRIGGER reject_frozen_link ON wms_review_v2.family_storage_attribute');
        }
    }

    public function testReferenceLockPreventsConcurrentDeletion(): void
    {
        $food = $this->attribute('FOOD');
        $other = new PDO(
            sprintf('pgsql:host=%s;port=%s;dbname=%s', $_ENV['POSTGRES_HOST'], $_ENV['POSTGRES_PORT'], $_ENV['POSTGRES_DB']),
            $_ENV['POSTGRES_USER'],
            $_ENV['POSTGRES_PASSWORD'],
            [PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION],
        );
        $other->exec("SET lock_timeout = '100ms'");
        $this->pdo->beginTransaction();
        self::assertSame([$food->code()->value()], $this->families->existingAttributeCodes([new StorageAttributeCode($food->code()->value())]));
        try {
            $other->prepare('DELETE FROM wms_review_v2.storage_attribute WHERE id = ?')->execute([$food->code()->value()]);
            self::fail('Referenced row must be locked.');
        } catch (PDOException $error) {
            self::assertSame('55P03', $error->getCode());
        }
        $this->pdo->rollBack();
        self::assertNotNull($this->attributes->find($food->id()));
    }

    public function testMigrationPreservesLegacyIdAndFamilyLink(): void
    {
        $food = $this->attribute('FOOD');
        $family = (new CreateItemFamily($this->families))->execute('ALIMENTOS', 'Alimentos', [$food->code()->value()]);
        $this->prepareLegacySchema();
        $this->pdo->prepare("UPDATE wms_review_v2.storage_attribute SET code = 'IS_FOOD' WHERE id = ?")->execute([$food->code()->value()]);
        $this->pdo->exec($this->migration());
        self::assertSame('FOOD', $this->attributes->find($food->id())?->code()->value());
        self::assertSame($food->code()->value(), $this->families->findByCode($family->code())?->attributes()[0]->value());
        $this->pdo->rollBack();
    }

    public function testMigrationRejectsCollisionsWithoutMerging(): void
    {
        $food = $this->attribute('FOOD');
        $this->prepareLegacySchema();
        $this->pdo->prepare("INSERT INTO wms_review_v2.storage_attribute (id, code, name) VALUES (?, 'IS_FOOD', 'Antiguo')")
            ->execute([Uuid::generate()]);
        try {
            $this->pdo->exec($this->migration());
            self::fail('Migration must reject ambiguous collisions.');
        } catch (PDOException $error) {
            self::assertStringContainsString('codes collide', $error->getMessage());
            $this->pdo->rollBack();
            self::assertSame(1, $this->countRows('storage_attribute'));
            self::assertEquals($food, $this->attributes->find($food->id()));
        }
    }

    private function prepareLegacySchema(): void
    {
        $this->pdo->beginTransaction();
        $this->pdo->exec('ALTER TABLE wms_review_v2.storage_attribute
            DROP CONSTRAINT storage_attribute_neutral_code,
            DROP CONSTRAINT storage_attribute_name_not_blank,
            DROP CONSTRAINT storage_attribute_group_code');
    }

    private function migration(): string
    {
        $sql = file_get_contents(__DIR__ . '/../../../database/migrations/007_storage_attribute_catalogue.sql');
        self::assertIsString($sql);
        return $sql;
    }

    private function attribute(string $code): StorageAttribute
    {
        return (new CreateStorageAttribute($this->attributes))->execute($code, $code);
    }

    private function countRows(string $table): int
    {
        $statement = $this->pdo->query('SELECT count(*) FROM wms_review_v2.' . $table);
        self::assertInstanceOf(\PDOStatement::class, $statement);
        return (int) $statement->fetchColumn();
    }
}
