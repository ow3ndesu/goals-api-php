<?php

namespace App\Middleware;

use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as Handler;
use Psr\Http\Message\ResponseInterface as Response;
use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Helpers;

class AuthMiddleware
{
    public function __invoke(Request $request, Handler $handler): Response
    {
        $auth = $request->getHeaderLine('Authorization');

        if (!$auth || !preg_match('/Bearer\s+(.*)$/i', $auth, $matches)) {
            $resp = new \Slim\Psr7\Response();
            return Helpers::jsonError($resp, 'Missing or invalid Authorization header', 401);
        }
        
        $token = $matches[1];

        try {
            $secret = $_ENV['JWT_SECRET'];
            $decoded = JWT::decode($token, new Key($secret, 'HS256'));
            
            // check exp
            $now = time();
            if (isset($decoded->exp) && $decoded->exp < $now) {
                $resp = new \Slim\Psr7\Response();
                return Helpers::jsonError($resp, 'Token expired', 401);
            }

            // attach user info to request
            $request = $request->withAttribute('user', [
                'id' => $decoded->sub,
                'email' => $decoded->email ?? null
            ]);

            return $handler->handle($request);
        } catch (\Exception $e) {
            $resp = new \Slim\Psr7\Response();
            return Helpers::jsonError($resp, 'Invalid token: ' . $e->getMessage(), 401);
        }
    }
}
