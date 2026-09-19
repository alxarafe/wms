<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoWarehouseStateProvider;
use flight\Engine;

final readonly class GetWarehouseStateController
{
    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function __invoke(string $id): void
    {
        $provider = new PdoWarehouseStateProvider(Database::getConnection());
        $state = $provider->stateFor($id);
        if ($state === null) {
            $this->app->json(['error' => 'Warehouse not found.'], 404);
            return;
        }

        $this->app->json($state);
    }
}
