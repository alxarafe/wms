<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\CreateStorageAttribute;
use Alxarafe\App\Application\Catalogue\ReadStorageAttributes;
use Alxarafe\App\Application\Catalogue\StorageAttributeConflict;
use Alxarafe\App\Application\Catalogue\StorageAttributeNotFound;
use Alxarafe\App\Domain\Catalogue\Entity\StorageAttribute;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoStorageAttributeRepository;
use flight\Engine;
use InvalidArgumentException;
use JsonException;

final readonly class StorageAttributeController
{
    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function register(): void
    {
        $this->app->route('POST /api/storage-attributes', $this->create(...));
        $this->app->route('GET /api/storage-attributes', $this->all(...));
        $this->app->route('GET /api/storage-attributes/@id', $this->get(...));
    }

    public function create(): void
    {
        $this->respond(function (): array {
            $input = CatalogueInput::parse($this->app->request()->getBody(), ['code', 'name', 'exclusive_group_code']);
            $service = new CreateStorageAttribute($this->repository());
            return self::record($service->execute($input->string('code'), $input->string('name'), $input->nullableString('exclusive_group_code')));
        }, 201);
    }

    public function get(string $id): void
    {
        $this->respond(fn (): array => self::record((new ReadStorageAttributes($this->repository()))->byId($id)));
    }

    public function all(): void
    {
        $this->respond(fn (): array => array_map(self::record(...), (new ReadStorageAttributes($this->repository()))->all()));
    }

    private function repository(): PdoStorageAttributeRepository
    {
        return new PdoStorageAttributeRepository(Database::getConnection());
    }

    /** @return array{id: string, code: string, name: string, exclusive_group_code: ?string} */
    private static function record(StorageAttribute $attribute): array
    {
        return [
            'id' => $attribute->id()->value(), 'code' => $attribute->code()->value(),
            'name' => $attribute->name(), 'exclusive_group_code' => $attribute->exclusiveGroupCode(),
        ];
    }

    /** @param callable(): array<mixed> $operation */
    private function respond(callable $operation, int $status = 200): void
    {
        try {
            $this->app->json($operation(), $status);
        } catch (JsonException) {
            $this->app->json(['error' => 'Invalid JSON body.'], 422);
        } catch (InvalidArgumentException $error) {
            $this->app->json(['error' => $error->getMessage()], 422);
        } catch (StorageAttributeNotFound $error) {
            $this->app->json(['error' => $error->getMessage()], 404);
        } catch (StorageAttributeConflict $error) {
            $this->app->json(['error' => $error->getMessage()], 409);
        }
    }
}
