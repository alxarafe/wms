<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Alxarafe\App\Infrastructure\Http\CreateItemFamilyController;
use flight\Engine;

$app = new Engine();

$app->route('GET /api/health', function () use ($app) {
    $app->json(['status' => 'ok', 'timestamp' => date('c')]);
});

$app->route('POST /api/item-families', new CreateItemFamilyController($app));

$app->start();
