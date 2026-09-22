<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\CreateItem;
use Alxarafe\App\Application\Catalogue\ItemConflict;
use Alxarafe\App\Application\Catalogue\ItemFamilyRepository;
use Alxarafe\App\Application\Catalogue\ItemRepository;
use Alxarafe\App\Application\Catalogue\UomRepository;
use Alxarafe\App\Domain\Catalogue\Entity\Item;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoItemFamilyRepository;
use Alxarafe\App\Infrastructure\Persistence\PdoItemRepository;
use Alxarafe\App\Infrastructure\Persistence\PdoUomRepository;
use flight\Engine;
use InvalidArgumentException;
use JsonException;
use PDOException;

final readonly class CreateItemController
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
            $required = ['sku', 'name', 'familyCode', 'baseUomCode', 'isBatchManaged', 'isExpirable'];
            foreach ($required as $field) {
                if (!array_key_exists($field, $body)) {
                    throw new InvalidArgumentException(
                        'Expected sku, name, familyCode, baseUomCode, isBatchManaged and isExpirable.'
                    );
                }
            }
            $isBoolean = static fn (string $field): bool => is_bool($body[$field] ?? null);
            if (
                !is_string($body['sku'])
                || !is_string($body['name'])
                || !is_string($body['familyCode'])
                || !is_string($body['baseUomCode'])
                || $isBoolean('isBatchManaged') === false
                || $isBoolean('isExpirable') === false
            ) {
                throw new InvalidArgumentException(
                    'Expected sku, name, familyCode, baseUomCode, isBatchManaged and isExpirable.'
                );
            }
            if (array_key_exists('baseCost', $body) || array_key_exists('currency', $body)) {
                throw new InvalidArgumentException('baseCost and currency are not accepted.');
            }
            $create = new CreateItem(
                $this->items(),
                $this->families(),
                $this->uoms(),
            );
            $item = $create->execute(
                $body['sku'],
                $body['name'],
                $body['familyCode'],
                $body['baseUomCode'],
                $body['isBatchManaged'],
                $body['isExpirable'],
            );
            $this->app->json($this->payload($item, $body['familyCode'], $body['baseUomCode']), 201);
        } catch (JsonException) {
            $this->app->json(['error' => 'Invalid JSON body.'], 400);
        } catch (InvalidArgumentException $error) {
            $this->app->json(['error' => $error->getMessage()], 400);
        } catch (ItemConflict $error) {
            $this->app->json(['error' => $error->getMessage()], 409);
        } catch (PDOException $error) {
            if ($error->getCode() === '23505') {
                $this->app->json(['error' => 'Item sku already exists.'], 409);
                return;
            }
            throw $error;
        }
    }

    private function items(): ItemRepository
    {
        return new PdoItemRepository(Database::getConnection());
    }

    private function families(): ItemFamilyRepository
    {
        return new PdoItemFamilyRepository(Database::getConnection());
    }

    private function uoms(): UomRepository
    {
        return new PdoUomRepository(Database::getConnection());
    }

    /** @return array<string, mixed> */
    private function payload(Item $item, string $familyCode, string $baseUomCode): array
    {
        return [
            'id' => $item->id()->value(),
            'sku' => $item->sku()->value(),
            'name' => $item->name(),
            'familyCode' => $familyCode,
            'baseUomCode' => $baseUomCode,
            'isBatchManaged' => $item->isBatchManaged(),
            'isExpirable' => $item->isExpirable(),
        ];
    }
}
