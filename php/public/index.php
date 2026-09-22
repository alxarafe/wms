<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Alxarafe\App\Infrastructure\Http\ConfigurationController;
use Alxarafe\App\Infrastructure\Http\CreateItemController;
use Alxarafe\App\Infrastructure\Http\CreateItemFamilyController;
use Alxarafe\App\Infrastructure\Http\CreateUomController;
use Alxarafe\App\Infrastructure\Http\GetItemFamiliesController;
use Alxarafe\App\Infrastructure\Http\GetItemsController;
use Alxarafe\App\Infrastructure\Http\GetUomsController;
use Alxarafe\App\Infrastructure\Http\GetWarehouseStateController;
use Alxarafe\App\Infrastructure\Http\PostIssuesController;
use Alxarafe\App\Infrastructure\Http\PostReceiptsController;
use flight\Engine;

$corsAllowedOrigin = getenv('CORS_ALLOWED_ORIGIN') ?: 'http://localhost:5173';
header('Access-Control-Allow-Origin: ' . $corsAllowedOrigin);
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');
header('Access-Control-Allow-Headers: Content-Type, Accept');
header('Access-Control-Max-Age: 86400');

if (($_SERVER['REQUEST_METHOD'] ?? 'GET') === 'OPTIONS') {
    http_response_code(204);
    exit;
}

$app = new Engine();

$app->map('notFound', static function () use ($app): void {
    $app->response()
        ->clearBody()
        ->status(404)
        ->write('<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <title>404 Not Found</title>
</head>
<body style="font-family: sans-serif; margin: 0; padding: 2rem;">
  <h1>404 Not Found</h1>
  <p>El recurso solicitado no existe en esta API.</p>
</body>
</html>')
        ->send();
});

$app->route('GET /api/health', function () use ($app) {
    $app->json(['status' => 'ok', 'timestamp' => date('c')]);
});

(new ConfigurationController($app))->register();

$app->route('POST /api/item-families', new CreateItemFamilyController($app));

$app->route('GET /api/item-families', new GetItemFamiliesController($app));

$app->route('POST /api/uoms', new CreateUomController($app));

$app->route('GET /api/uoms', new GetUomsController($app));

$app->route('POST /api/items', new CreateItemController($app));

$app->route('GET /api/items', new GetItemsController($app));

$app->route('GET /api/warehouses/@id/state', new GetWarehouseStateController($app));

$app->route('POST /api/receipts', new PostReceiptsController($app));

$app->route('POST /api/issues', new PostIssuesController($app));

$app->start();
