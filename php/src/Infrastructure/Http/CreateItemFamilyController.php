<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\CreateItemFamily;
use Alxarafe\App\Application\Catalogue\ItemFamilyConflict;
use Alxarafe\App\Domain\Rules\ValueObject\AttributeCode;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoItemFamilyRepository;
use flight\Engine;
use InvalidArgumentException;
use JsonException;
use PDOException;

final readonly class CreateItemFamilyController
{
    /** @param Engine<object> $app */
    public function __construct(private Engine $app)
    {
    }

    public function __invoke(): void
    {
        try {
            $body = json_decode($this->app->request()->getBody(), true, 512, JSON_THROW_ON_ERROR);
            if (!is_array($body)) {
                throw new JsonException('Invalid JSON body.');
            }
            if (
                !isset($body['code'], $body['name'], $body['attributes'])
                || !is_string($body['code'])
                || !is_string($body['name'])
                || !is_array($body['attributes'])
                || !array_is_list($body['attributes'])
            ) {
                throw new InvalidArgumentException('Expected code, name and attributes.');
            }
            foreach ($body['attributes'] as $attribute) {
                if (!is_string($attribute)) {
                    throw new InvalidArgumentException('Attribute codes must be strings.');
                }
            }
            $useCase = new CreateItemFamily(new PdoItemFamilyRepository(Database::getConnection()));
            $family = $useCase->execute($body['code'], $body['name'], $body['attributes']);
            $this->app->json([
                'id' => $family->id()->value(),
                'code' => $family->code()->value(),
                'name' => $family->name(),
                'attributes' => array_map(
                    static fn (AttributeCode $attribute): string => $attribute->value(),
                    $family->attributes(),
                ),
            ], 201);
        } catch (JsonException) {
            $this->app->json(['error' => 'Invalid JSON body.'], 400);
        } catch (InvalidArgumentException $error) {
            $this->app->json(['error' => $error->getMessage()], 400);
        } catch (ItemFamilyConflict $error) {
            $this->app->json(['error' => $error->getMessage()], 409);
        } catch (PDOException $error) {
            if ($error->getCode() === '23505') {
                $this->app->json(['error' => 'Item family code already exists.'], 409);
                return;
            }
            throw $error;
        }
    }
}
