<?php
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\DB;
use App\Helpers;

use App\Services\GoalService;

return function (App $app, $authMiddleware) {

    // GET /goals
    $app->get('/goals', function (Request $req, Response $resp) {
        $user = $req->getAttribute('user');
        $query = $req->getQueryParams();
        
        $service = new GoalService(DB::get());
        $goals = $service->getGoals($user, $query);

        return Helpers::jsonSuccess($resp, $goals);
    })->add($authMiddleware);

    // POST /goals
    $app->post('/goals', function (Request $req, Response $resp) {
        $user = $req->getAttribute('user');
        $body = $req->getParsedBody();

        $service = new GoalService(DB::get());
        $serviceResponse = $service->addGoal($user, $body);

        if (isset($serviceResponse['error']) && $serviceResponse['error']) {
            return Helpers::jsonError($resp, $serviceResponse['message'], $serviceResponse['code']);
        }

        return Helpers::jsonSuccess($resp, $serviceResponse, 201);
    })->add($authMiddleware);

    // GET /goals/{id}
    $app->get('/goals/{id}', function (Request $req, Response $resp, $param) {
        $user = $req->getAttribute('user'); 
        $id = (int)$param['id'];
        
        $service = new GoalService(DB::get());
        $serviceResponse = $service->getGoal($user, $id);

        if (isset($serviceResponse['error']) && $serviceResponse['error']) {
            return Helpers::jsonError($resp, $serviceResponse['message'], $serviceResponse['code']);
        }

        return Helpers::jsonSuccess($resp, $serviceResponse);
    })->add($authMiddleware);

    // PUT /goals/{id}
    $app->put('/goals/{id}', function (Request $req, Response $resp, $param) {
        $user = $req->getAttribute('user'); 
        $id = (int)$param['id']; 
        $body = $req->getParsedBody();

        $service = new GoalService(DB::get());
        $serviceResponse = $service->updateGoal($user, $body, $id);

        if (isset($serviceResponse['error']) && $serviceResponse['error']) {
            return Helpers::jsonError($resp, $serviceResponse['message'], $serviceResponse['code']);
        }

        return Helpers::jsonSuccess($resp, $serviceResponse);
    })->add($authMiddleware);

    $app->patch('/goals/{id}', function (Request $req, Response $resp, $param) {
        $user = $req->getAttribute('user'); 
        $id = (int)$param['id']; 
        $body = $req->getParsedBody();

        $service = new GoalService(DB::get());
        $serviceResponse = $service->updateGoalPartially($user, $body, $id);

        if (isset($serviceResponse['error']) && $serviceResponse['error']) {
            return Helpers::jsonError($resp, $serviceResponse['message'], $serviceResponse['code']);
        }

        return Helpers::jsonSuccess($resp, $serviceResponse);
    })->add($authMiddleware);

    // DELETE /goals/{id}
    $app->delete('/goals/{id}', function (Request $req, Response $resp, $param) {
        $user = $req->getAttribute('user'); 
        $id = (int)$param['id'];
        
        $service = new GoalService(DB::get());
        $serviceResponse = $service->deleteGoal($user, $id);

        if (isset($serviceResponse['error']) && $serviceResponse['error']) {
            return Helpers::jsonError($resp, $serviceResponse['message'], $serviceResponse['code']);
        }

        return Helpers::jsonSuccess($resp, $serviceResponse);
    })->add($authMiddleware);

};
