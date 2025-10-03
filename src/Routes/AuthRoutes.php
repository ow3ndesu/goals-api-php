<?php
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\DB;
use App\Helpers;

use App\Services\AuthService;

return function (App $app, $authMiddleware) {

    // POST /auth/login
    $app->post('/auth/login', function (Request $req, Response $resp) {
        $body = $req->getParsedBody();
        $ip = $req->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        $service = new AuthService(DB::get());
        $serviceResponse = $service->login($body, $ip);

        if (isset($serviceResponse['error']) && $serviceResponse['error']) {
            return Helpers::jsonError($resp, $serviceResponse['message'], $serviceResponse['code']);
        }

        return Helpers::jsonSuccess($resp, $serviceResponse);
    });

    // POST /auth/refresh
    $app->post('/auth/refresh', function (Request $req, Response $resp) {
        $body = $req->getParsedBody();
        
        $service = new AuthService(DB::get());
        $serviceResponse = $service->refreshToken($body);

        if (isset($serviceResponse['error']) && $serviceResponse['error']) {
            return Helpers::jsonError($resp, $serviceResponse['message'], $serviceResponse['code']);
        }

        return Helpers::jsonSuccess($resp, $serviceResponse);
    });
};
