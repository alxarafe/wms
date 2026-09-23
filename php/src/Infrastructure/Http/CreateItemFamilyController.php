<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\CreateItemFamily;
use Alxarafe\App\Application\Catalogue\ItemFamilyConflict;
use Alxarafe\App\Application\Catalogue\StorageAttributeNotFound;
use Alxarafe\App\Domain\Catalogue\ValueObject\StorageAttributeCode;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoItemFamilyRepository;
use flight\Engine;
use InvalidArgumentException;
use JsonException;

final readonly class CreateItemFamilyController
{
    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function __invoke(): void
    {
        try {
            $input = CatalogueInput::parse($this->app->request()->getBody(), ['code', 'name', 'attributes']);
            $useCase = new CreateItemFamily(new PdoItemFamilyRepository(Database::getConnection()));
            $family = $useCase->execute($input->string('code'), $input->string('name'), $input->strings('attributes'));
            $this->app->json([
                'id' => $family->id()->value(),
                'code' => $family->code()->value(),
                'name' => $family->name(),
                'attributes' => array_map(
                    static fn (StorageAttributeCode $attribute): string => $attribute->value(),
                    $family->attributes(),
                ),
            ], 201);
        } catch (JsonException) {
            $this->app->json(['error' => 'Invalid JSON body.'], 422);
        } catch (InvalidArgumentException $error) {
            $this->app->json(['error' => $error->getMessage()], 422);
        } catch (ItemFamilyConflict $error) {
            $this->app->json(['error' => $error->getMessage()], 409);
        } catch (StorageAttributeNotFound $error) {
            $this->app->json(['error' => $error->getMessage()], 404);
        }
    }
}
