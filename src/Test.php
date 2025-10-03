<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

use App\Middleware\AuthMiddleware;
use App\Middleware\ErrorHandlerMiddleware;

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();
$errorMiddleware = $app->addErrorMiddleware(true, true, true);

// Global error handler
$errorMiddleware->setDefaultErrorHandler(new ErrorHandlerMiddleware());

// Middleware
$authMiddleware = new AuthMiddleware();

// Routes
(require __DIR__ . '/Routes/SwaggerRoutes.php')($app);
(require __DIR__ . '/Routes/AuthRoutes.php')($app, $authMiddleware);
(require __DIR__ . '/Routes/GoalRoutes.php')($app, $authMiddleware);

return $app;
