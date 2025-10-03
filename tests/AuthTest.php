<?php

namespace Tests;

use Tests\TestCase;

class AuthTest extends TestCase
{
    public function testLoginWithInvalidCredentials(): void
    {
        $req = $this->createRequest('POST', '/auth/login', body: [
            'email' => 'bad@example.com',
            'password' => 'wrong'
        ]);

        $resp = $this->app->handle($req);

        $this->assertEquals(401, $resp->getStatusCode());
    }

    public function testLoginWithValidCredentials(): void
    {
        $req = $this->createRequest('POST', '/auth/login', body: [
            'email' => 'demo@example.com',
            'password' => 'password123'
        ]);

        $resp = $this->app->handle($req);

        $this->assertEquals(200, $resp->getStatusCode());

        $data = $this->parseJson($resp);
        $this->assertArrayHasKey('access_token', $data);
    }
}
