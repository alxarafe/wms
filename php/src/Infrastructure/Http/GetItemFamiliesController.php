<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\ListItemFamilies;
use Alxarafe\App\Domain\Catalogue\Entity\ItemFamily;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoItemFamilyRepository;
use flight\Engine;

final readonly class GetItemFamiliesController
{
    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function __invoke(): void
    {
        $useCase = new ListItemFamilies(new PdoItemFamilyRepository(Database::getConnection()));
        $families = array_map(
            static fn (ItemFamily $family): array => [
                'id' => $family->id()->value(),
                'code' => $family->code()->value(),
                'name' => $family->name(),
                'attributes' => array_map(
                    static fn (StorageAttributeCode $attribute): string => $attribute->value(),
                    $family->attributes(),
                ),
            ],
            $useCase->execute(),
        );
        $this->app->json($families, 200);
    }
}
