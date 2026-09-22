<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\ListUoms;
use Alxarafe\App\Domain\Catalogue\ValueObject\Uom;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoUomRepository;
use flight\Engine;

final readonly class GetUomsController
{
    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function __invoke(): void
    {
        $uoms = array_map(
            static fn (Uom $uom): array => [
                'id' => $uom->id()->value(),
                'code' => $uom->code()->value(),
                'description' => $uom->description(),
            ],
            (new ListUoms(new PdoUomRepository(Database::getConnection())))->execute(),
        );
        $this->app->json($uoms, 200);
    }
}
