<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use flight\Engine;

$app = new Engine();

$app->route('GET /api/health', function () use ($app) {
    $app->json(['status' => 'ok', 'timestamp' => date('c')]);
});

$app->start();
