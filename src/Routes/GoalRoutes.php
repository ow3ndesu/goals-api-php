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
    $app->put('/goals/{id}', function (Request $req, Response $resp, $a) {
        $user=$req->getAttribute('user'); $id=(int)$a['id']; $b=$req->getParsedBody();
        $title=$b['title']??null; $target=$b['target_amount']??null; $saved=$b['saved_amount']??null;

        $errs=[];
        if($title!==null && trim($title)==='') $errs['title']='Cannot be empty';
        if($target!==null && (!is_numeric($target)||$target<0)) $errs['target_amount']='Must be non-negative';
        if($saved!==null && (!is_numeric($saved)||$saved<0)) $errs['saved_amount']='Must be non-negative';
        if($errs) return Helpers::jsonError($resp,'Validation failed',422,$errs);

        $pdo=DB::get();
        $chk=$pdo->prepare("SELECT id FROM goals WHERE id=:id AND user_id=:u");
        $chk->execute([':id'=>$id,':u'=>$user['id']]); if(!$chk->fetch()) return Helpers::jsonError($resp,'Not found',404);

        $fields=[];$p=[':id'=>$id];
        if($title!==null){$fields[]="title=:t";$p[':t']=trim($title);}
        if($target!==null){$fields[]="target_amount=:ta";$p[':ta']=$target;}
        if($saved!==null){$fields[]="saved_amount=:sa";$p[':sa']=$saved;}
        if(!$fields) return Helpers::jsonError($resp,'No fields',400);

        $pdo->prepare("UPDATE goals SET ".implode(',',$fields)." WHERE id=:id")->execute($p);
        $g=$pdo->query("SELECT id,title,target_amount,saved_amount,created_at,updated_at FROM goals WHERE id=$id")->fetch();
        return Helpers::jsonSuccess($resp,['goal'=>$g]);
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
