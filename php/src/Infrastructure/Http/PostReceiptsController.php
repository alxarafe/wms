<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Movement\StockOperationException;
use Alxarafe\App\Domain\Inventory\ValueObject\Quantity;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoStockOperationsProvider;
use flight\Engine;
use InvalidArgumentException;
use JsonException;

final readonly class PostReceiptsController
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
            $locationId = self::requireString($body, 'locationId');
            $itemCode = self::requireString($body, 'itemCode');
            $unit = self::requireString($body, 'unit');
            $quantity = self::requireNumber($body, 'quantity');
            $batchCode = self::optionalString($body['batchCode'] ?? null);

            $provider = new PdoStockOperationsProvider(Database::getConnection());
            $view = $provider->receive($locationId, $itemCode, Quantity::fromDecimal($quantity, $unit), $batchCode);
            $this->app->json($view, 201);
        } catch (JsonException) {
            $this->app->json(['error' => 'Invalid JSON body.'], 400);
        } catch (InvalidArgumentException $error) {
            $this->app->json(['error' => $error->getMessage()], 400);
        } catch (StockOperationException $error) {
            $this->app->json(['error' => $error->getMessage()], $error->status());
        }
    }

    /** @param array<string, mixed> $body */
    private static function requireString(array $body, string $key): string
    {
        if (!isset($body[$key]) || !is_string($body[$key]) || $body[$key] === '') {
            throw new InvalidArgumentException("Expected a non-empty string for {$key}.");
        }
        return $body[$key];
    }

    /** @param array<string, mixed> $body */
    private static function requireNumber(array $body, string $key): int|float
    {
        if (!isset($body[$key]) || (!is_int($body[$key]) && !is_float($body[$key]))) {
            throw new InvalidArgumentException("Expected a number for {$key}.");
        }
        return $body[$key];
    }

    private static function optionalString(mixed $value): ?string
    {
        if ($value === null) {
            return null;
        }
        if (!is_string($value) || $value === '') {
            throw new InvalidArgumentException('Expected a string for batchCode.');
        }
        return $value;
    }
}
