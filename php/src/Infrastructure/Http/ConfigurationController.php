<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Configuration\ConfigurationConflict;
use Alxarafe\App\Application\Configuration\ConfigurationNotFound;
use Alxarafe\App\Application\Configuration\ConfigureWarehouse;
use Alxarafe\App\Domain\Configuration\HandlingUnitType;
use Alxarafe\App\Domain\Configuration\LocationType;
use Alxarafe\App\Domain\Configuration\LocationTypeHuPolicy;
use Alxarafe\App\Domain\Configuration\WarehouseConfiguration;
use Alxarafe\App\Domain\Configuration\WarehouseFormatLocked;
use Alxarafe\App\Domain\Shared\ValueObject\Uuid;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\ConfigurationRecord;
use Alxarafe\App\Infrastructure\Persistence\PdoConfigurationRepository;
use flight\Engine;
use InvalidArgumentException;
use JsonException;

final readonly class ConfigurationController
{
    private const FORMAT_FIELDS = [
        'aisle_digits', 'bay_digits', 'level_digits', 'uses_zones', 'separator', 'include_zone_in_code',
    ];

    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function register(): void
    {
        $this->app->route('POST /api/warehouses', $this->createWarehouse(...));
        $this->app->route('GET /api/warehouses', $this->warehouses(...));
        $this->app->route('PUT /api/warehouses/@warehouseId/format', $this->changeFormat(...));
        $this->app->route('POST /api/handling-unit-types', $this->createHandlingUnitType(...));
        $this->app->route('GET /api/handling-unit-types', $this->handlingUnitTypes(...));
        $this->app->route('POST /api/warehouses/@warehouseId/location-types', $this->createLocationType(...));
        $this->app->route('GET /api/warehouses/@warehouseId/location-types', $this->locationTypes(...));
        $path = '/api/warehouses/@warehouseId/location-types/@locationTypeId/hu-policies';
        $this->app->route('POST ' . $path, $this->createPolicy(...));
        $this->app->route('GET ' . $path, $this->policies(...));
    }

    public function createWarehouse(): void
    {
        $this->respond(function (ConfigureWarehouse $useCase): array {
            $input = $this->input(['code', 'name', ...self::FORMAT_FIELDS]);
            return ConfigurationRecord::encode($useCase->createWarehouse(new WarehouseConfiguration(
                Uuid::generate(),
                $input->string('code'),
                $input->string('name'),
                $input->format(),
            )));
        }, 201);
    }

    public function createHandlingUnitType(): void
    {
        $this->respond(function (ConfigureWarehouse $useCase): array {
            $input = $this->input(['code', 'name', 'is_active']);
            return ConfigurationRecord::encode($useCase->createHandlingUnitType(new HandlingUnitType(
                Uuid::generate(),
                $input->string('code'),
                $input->string('name'),
                $input->boolean('is_active', true),
            )));
        }, 201);
    }

    public function createLocationType(string $warehouseId): void
    {
        $this->respond(function (ConfigureWarehouse $useCase) use ($warehouseId): array {
            $input = $this->input(['code', 'name', 'max_locations_per_item', 'allows_multi_sku', 'allows_multi_batch']);
            return ConfigurationRecord::encode($useCase->createLocationType(new LocationType(
                Uuid::generate(),
                $warehouseId,
                $input->string('code'),
                $input->string('name'),
                $input->optionalInteger('max_locations_per_item'),
                $input->boolean('allows_multi_sku'),
                $input->boolean('allows_multi_batch'),
            )));
        }, 201);
    }

    public function createPolicy(string $warehouseId, string $locationTypeId): void
    {
        $this->respond(function (ConfigureWarehouse $useCase) use ($warehouseId, $locationTypeId): array {
            $input = $this->input([
                'handling_unit_type_id', 'accepts_full', 'accepts_partial', 'allows_breakdown', 'allows_full_dispatch',
            ]);
            return ConfigurationRecord::encode($useCase->createPolicy($warehouseId, new LocationTypeHuPolicy(
                $locationTypeId,
                $input->string('handling_unit_type_id'),
                $input->boolean('accepts_full'),
                $input->boolean('accepts_partial'),
                $input->boolean('allows_breakdown'),
                $input->boolean('allows_full_dispatch'),
            )));
        }, 201);
    }

    public function changeFormat(string $warehouseId): void
    {
        $this->respond(fn (ConfigureWarehouse $useCase): array => ConfigurationRecord::encode(
            $useCase->changeFormat($warehouseId, $this->input(self::FORMAT_FIELDS)->format()),
        ));
    }

    public function warehouses(): void
    {
        $this->respond(fn (ConfigureWarehouse $useCase): array => [
            'warehouses' => array_map(ConfigurationRecord::encode(...), $useCase->warehouses()),
        ]);
    }

    public function handlingUnitTypes(): void
    {
        $this->respond(fn (ConfigureWarehouse $useCase): array => [
            'handling_unit_types' => array_map(ConfigurationRecord::encode(...), $useCase->handlingUnitTypes()),
        ]);
    }

    public function locationTypes(string $warehouseId): void
    {
        $this->respond(fn (ConfigureWarehouse $useCase): array => [
            'location_types' => array_map(ConfigurationRecord::encode(...), $useCase->locationTypes($warehouseId)),
        ]);
    }

    public function policies(string $warehouseId, string $locationTypeId): void
    {
        $this->respond(fn (ConfigureWarehouse $useCase): array => [
            'hu_policies' => array_map(ConfigurationRecord::encode(...), $useCase->policies($warehouseId, $locationTypeId)),
        ]);
    }

    /** @param list<string> $fields */
    private function input(array $fields): ConfigurationInput
    {
        return ConfigurationInput::parse($this->app->request()->getBody(), $fields);
    }

    /** @param callable(ConfigureWarehouse): array<mixed> $action */
    private function respond(callable $action, int $status = 200): void
    {
        try {
            $result = $action(new ConfigureWarehouse(new PdoConfigurationRepository(Database::getConnection())));
            $this->app->json($result, $status);
        } catch (JsonException) {
            $this->app->json(['error' => 'Invalid JSON body.'], 400);
        } catch (InvalidArgumentException $error) {
            $this->app->json(['error' => $error->getMessage()], 400);
        } catch (ConfigurationNotFound $error) {
            $this->app->json(['error' => $error->getMessage()], 404);
        } catch (ConfigurationConflict | WarehouseFormatLocked $error) {
            $this->app->json(['error' => $error->getMessage()], 409);
        }
    }
}
