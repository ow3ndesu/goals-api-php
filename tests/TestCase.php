<?php

namespace Tests;

use PHPUnit\Framework\TestCase as BaseTestCase;
use Slim\App;
use Slim\Psr7\Factory\ServerRequestFactory;
use Slim\Psr7\Factory\StreamFactory;

class TestCase extends BaseTestCase
{
    protected App $app;

    protected function setUp(): void
    {
        $this->app = (require __DIR__ . '/../src/Test.php');
    }

    /**
     * Helper: Create a PSR-7 request for testing
     */
    protected function createRequest(
        string $method,
        string $uri,
        array $headers = [],
        array $body = null
    ) {
        $requestFactory = new ServerRequestFactory();
        $request = $requestFactory->createServerRequest($method, $uri);

        foreach ($headers as $name => $value) {
            $request = $request->withHeader($name, $value);
        }

        if ($body !== null) {
            $stream = (new StreamFactory())->createStream(json_encode($body));
            $request = $request
                ->withBody($stream)
                ->withHeader('Content-Type', 'application/json');
        }

        return $request;
    }

    /**
     * Helper: Parse JSON response into array
     */
    protected function parseJson($response): array
    {
        $body = (string) $response->getBody();
        return json_decode($body, true) ?? [];
    }
}
