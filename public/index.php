<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use App\AuthMiddleware;
use Dotenv\Dotenv;

// Load env
$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

// Slim app
$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Middleware instance
$authMiddleware = new AuthMiddleware();

// Swagger UI Endpoint
(require __DIR__ . '/../src/Routes/SwaggerRoutes.php')($app);

// Include routes
(require __DIR__ . '/../src/Routes/AuthRoutes.php')($app, $authMiddleware);
(require __DIR__ . '/../src/Routes/GoalRoutes.php')($app, $authMiddleware);

// Run
$app->run();
