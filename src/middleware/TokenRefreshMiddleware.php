<?php

namespace App\Middleware;

use App\Services\AuthService;
use Psr\Http\Message\ResponseInterface;
use Psr\Http\Message\ServerRequestInterface;
use Psr\Http\Server\RequestHandlerInterface;
use Psr\Http\Server\MiddlewareInterface;
use Slim\Psr7\Response;

class TokenRefreshMiddleware implements MiddlewareInterface
{
    private AuthService $authService;

    public function __construct(AuthService $authService)
    {
        $this->authService = $authService;
    }

    public function process(ServerRequestInterface $request, RequestHandlerInterface $handler): ResponseInterface
    {
        $authHeader = $request->getHeaderLine('Authorization');

        if (!$authHeader || !str_starts_with($authHeader, 'Bearer ')) {
            return $handler->handle($request);
        }

        $accessToken = substr($authHeader, 7);

        // Try to validate current access token
        $payload = $this->authService->validateAccessToken($accessToken);
        if ($payload !== null) {
            // Token still valid
            return $handler->handle($request);
        }

        // Token expired, check refresh token
        $refreshToken = $request->getHeaderLine('X-Refresh-Token')
            ?: ($_COOKIE['refresh_token'] ?? null);

        if (!$refreshToken) {
            return $this->unauthorizedResponse('Access token expired. No refresh token found.');
        }

        $newTokens = $this->authService->refreshTokens($refreshToken);
        if (!$newTokens || isset($newTokens['error'])) {
            return $this->unauthorizedResponse('Invalid refresh token.');
        }

        // Re-attach new token headers
        $response = $handler->handle(
            $request->withHeader('Authorization', 'Bearer ' . $newTokens['access_token'])
        );

        // Attach new access & refresh tokens in response headers
        return $response
            ->withHeader('X-New-Access-Token', $newTokens['access_token'])
            ->withHeader('X-New-Refresh-Token', $newTokens['refresh_token']);
    }

    private function unauthorizedResponse(string $message): ResponseInterface
    {
        $response = new Response();
        $response->getBody()->write(json_encode([
            'error' => true,
            'message' => $message
        ]));
        return $response
            ->withHeader('Content-Type', 'application/json')
            ->withStatus(401);
    }
}
