<?php

declare(strict_types=1);

require __DIR__ . '/../vendor/autoload.php';

use Alxarafe\App\Application\UseCase\CreateGreetingUseCase;
use Alxarafe\App\Application\UseCase\GetGreetingUseCase;
use Alxarafe\App\Infrastructure\Config\Database;
use Alxarafe\App\Infrastructure\Config\DotEnvLoader;
use Alxarafe\App\Infrastructure\Controller\GreetingController;
use Alxarafe\App\Infrastructure\Persistence\InMemoryGreetingRepository;
use Alxarafe\App\Infrastructure\Persistence\PostgresGreetingRepository;
use flight\Engine;

DotEnvLoader::load(__DIR__ . '/../');

$app = new Engine();

$repo = resolveRepository();
$createUseCase = new CreateGreetingUseCase($repo);
$getUseCase = new GetGreetingUseCase($repo);
$controller = new GreetingController($createUseCase, $getUseCase);

$app->route('GET /api/health', function () use ($app) {
    $app->json(['status' => 'ok', 'timestamp' => date('c')]);
});

$app->route('GET /api/greet', function () use ($app, $controller) {
    $controller->greet($app);
});

$app->route('GET /api/greetings', function () use ($app, $controller) {
    $controller->getAll($app);
});

$app->start();

function resolveRepository(): PostgresGreetingRepository|InMemoryGreetingRepository
{
    try {
        $pdo = Database::getConnection();
        $pdo->query('SELECT 1 FROM greetings LIMIT 1');

        return new PostgresGreetingRepository($pdo);
    } catch (\Throwable) {
        return new InMemoryGreetingRepository();
    }
}
