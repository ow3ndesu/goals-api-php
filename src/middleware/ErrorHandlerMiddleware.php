<?php

namespace App\Middleware;

use Psr\Http\Message\ResponseInterface as Response;
use Psr\Http\Message\ServerRequestInterface as Request;
use Psr\Http\Server\RequestHandlerInterface as RequestHandler;
use Slim\Exception\HttpException;
use Slim\Exception\HttpNotFoundException;
use Slim\Exception\HttpMethodNotAllowedException;
use Slim\Exception\HttpUnauthorizedException;
use Slim\Psr7\Response as SlimResponse;
use Slim\Psr7\Factory\StreamFactory;
use Throwable;

class ErrorHandlerMiddleware
{
    public function __invoke(Request $req, Throwable $e, bool $displayErrorDetails): Response
    {
        // Default status
        $status = 500;

        if ($e instanceof HttpUnauthorizedException) {
            $status = 401;
        } elseif ($e instanceof HttpNotFoundException) {
            $status = 404;
        } elseif ($e instanceof HttpMethodNotAllowedException) {
            $status = 405;
        } elseif ($e instanceof HttpException) {
            $status = $e->getCode();
        }

        $response = new SlimResponse();
        $response = $response
            ->withStatus($status)
            ->withHeader('Content-Type', 'application/json');

        $stream = (new StreamFactory())->createStream(json_encode([
            'error' => true,
            'message' => $e->getMessage(),
            'status' => $status
        ]));

        return $response->withBody($stream);
    }
}