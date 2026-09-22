<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\ItemView;
use Alxarafe\App\Application\Catalogue\ListItems;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoItemRepository;
use flight\Engine;

final readonly class GetItemsController
{
    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function __invoke(): void
    {
        $views = (new ListItems(new PdoItemRepository(Database::getConnection())))->execute();
        $this->app->json(array_map(
            static fn (ItemView $view): array => [
                'id' => $view->item->id()->value(),
                'sku' => $view->item->sku()->value(),
                'name' => $view->item->name(),
                'familyCode' => $view->familyCode,
                'baseUomCode' => $view->baseUomCode,
                'isBatchManaged' => $view->item->isBatchManaged(),
                'isExpirable' => $view->item->isExpirable(),
            ],
            $views,
        ));
    }
}
