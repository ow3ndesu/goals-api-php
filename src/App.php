<?php
require __DIR__ . '/../vendor/autoload.php';

use Slim\Factory\AppFactory;
use Dotenv\Dotenv;

use App\Middleware\AuthMiddleware;
use App\Middleware\ErrorHandlerMiddleware;
use App\Middleware\TokenRefreshMiddleware;

use App\Services\AuthService;
use App\DB;

$dotenv = Dotenv::createImmutable(__DIR__ . "/..");
$dotenv->load();

$app = AppFactory::create();
$app->addBodyParsingMiddleware();
$app->addRoutingMiddleware();

// Error Handler Middleware
$errorMiddleware = $app->addErrorMiddleware(true, true, true);
$errorMiddleware->setDefaultErrorHandler(new ErrorHandlerMiddleware());

// Refresh Token Middleware
$authService = new AuthService(DB::get());
$app->add(new TokenRefreshMiddleware($authService));

// Auth Middleware
$authMiddleware = new AuthMiddleware();

// Routes
(require __DIR__ . '/Routes/SwaggerRoutes.php')($app);
(require __DIR__ . '/Routes/AuthRoutes.php')($app, $authMiddleware);
(require __DIR__ . '/Routes/GoalRoutes.php')($app, $authMiddleware);

return $app;
