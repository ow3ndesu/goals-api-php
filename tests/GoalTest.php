<?php

namespace Tests;

use Slim\App;

final class GoalsTest extends TestCase
{
    private string $token;

    protected function setUp(): void
    {
        parent::setUp();

        // First login to get a token
        $req = $this->createRequest('POST', '/auth/login', body: [
            'email' => 'demo@example.com',
            'password' => 'password123'
        ]);
        $resp = $this->app->handle($req);

        $data = $this->parseJson($resp);
        $this->token = $data['access_token'] ?? null;
        $this->assertNotNull($this->token, "Failed to get access token in GoalsTest setup.");
    }

    protected function createAuthedRequest(string $method, string $uri, array $query = [], array $body = [])
    {
        $headers = [
            'Authorization' => "Bearer {$this->token}",
            'Content-Type'  => 'application/json'
        ];

        return $this->createRequest($method, $uri, $query, $headers, $body);
    }

    public function testAddGoal(): void
    {
        $req = $this->createAuthedRequest('POST', '/goals', body: [
            'title' => 'Buy a new laptop',
            'target_amount' => 2000,
            'saved_amount' => 200
        ]);

        $resp = $this->app->handle($req);
        $this->assertEquals(201, $resp->getStatusCode());

        $data = $this->parseJson($resp);
        $this->assertArrayHasKey('goal', $data);
        $this->assertEquals('Buy a new laptop', $data['goal']['title']);
    }

    public function testGetGoals(): void
    {
        $req = $this->createAuthedRequest('GET', '/goals');

        $resp = $this->app->handle($req);
        $this->assertEquals(200, $resp->getStatusCode());

        $data = $this->parseJson($resp);
        $this->assertArrayHasKey('data', $data);
        $this->assertArrayHasKey('pagination', $data);
    }

    public function testUpdateGoalWithPut(): void
    {
        // Create a goal
        $req = $this->createAuthedRequest('POST', '/goals', body: [
            'title' => 'Old Title',
            'target_amount' => 100,
            'saved_amount' => 20
        ]);
        $resp = $this->app->handle($req);
        $data = $this->parseJson($resp);
        $goalId = $data['goal']['id'];

        // Full update (PUT)
        $putReq = $this->createAuthedRequest('PUT', "/goals/$goalId", body: [
            'title' => 'Updated Goal Title',
            'target_amount' => 500,
            'saved_amount' => 300
        ]);

        $putResp = $this->app->handle($putReq);
        $this->assertEquals(200, $putResp->getStatusCode());

        $putData = $this->parseJson($putResp);
        $this->assertEquals('Updated Goal Title', $putData['goal']['title']);
        $this->assertEquals(500, $putData['goal']['target_amount']);
        $this->assertEquals(300, $putData['goal']['saved_amount']);
    }

    public function testUpdateGoalPartially(): void
    {
        // Create a goal
        $req = $this->createAuthedRequest('POST', '/goals', body: [
            'title' => 'Test Goal',
            'target_amount' => 1000,
            'saved_amount' => 100
        ]);
        $resp = $this->app->handle($req);
        $data = $this->parseJson($resp);
        $goalId = $data['goal']['id'];

        // Now patch it
        $patchReq = $this->createAuthedRequest('PATCH', "/goals/$goalId", body: [
            'saved_amount' => 500
        ]);

        $patchResp = $this->app->handle($patchReq);
        $this->assertEquals(200, $patchResp->getStatusCode());

        $patchData = $this->parseJson($patchResp);
        $this->assertEquals(500, $patchData['goal']['saved_amount']);
    }

    public function testDeleteGoal(): void
    {
        // Create a goal
        $req = $this->createAuthedRequest('POST', '/goals', body: [
            'title' => 'Delete Me',
            'target_amount' => 300,
            'saved_amount' => 50
        ]);
        $resp = $this->app->handle($req);
        $data = $this->parseJson($resp);
        $goalId = $data['goal']['id'];

        // Delete it
        $delReq = $this->createAuthedRequest('DELETE', "/goals/$goalId");

        $delResp = $this->app->handle($delReq);
        $this->assertEquals(200, $delResp->getStatusCode());

        $delData = $this->parseJson($delResp);
        $this->assertEquals('Deleted', $delData['message']);
    }
}
