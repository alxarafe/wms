<?php

declare(strict_types=1);

namespace Alxarafe\App\Infrastructure\Http;

use Alxarafe\App\Application\Catalogue\CreateUom;
use Alxarafe\App\Application\Catalogue\UomConflict;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Persistence\PdoUomRepository;
use flight\Engine;
use InvalidArgumentException;
use JsonException;
use PDOException;

final readonly class CreateUomController
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
                !isset($body['code'], $body['description'])
                || !is_string($body['code'])
                || !is_string($body['description'])
            ) {
                throw new InvalidArgumentException('Expected code and description.');
            }
            $uom = (new CreateUom(new PdoUomRepository(Database::getConnection())))->execute(
                $body['code'],
                $body['description'],
            );
            $this->app->json([
                'id' => $uom->id()->value(),
                'code' => $uom->code()->value(),
                'description' => $uom->description(),
            ], 201);
        } catch (JsonException) {
            $this->app->json(['error' => 'Invalid JSON body.'], 400);
        } catch (InvalidArgumentException $error) {
            $this->app->json(['error' => $error->getMessage()], 400);
        } catch (UomConflict $error) {
            $this->app->json(['error' => $error->getMessage()], 409);
        } catch (PDOException $error) {
            if ($error->getCode() === '23505') {
                $this->app->json(['error' => 'Uom code already exists.'], 409);
                return;
            }
            throw $error;
        }
    }
}
