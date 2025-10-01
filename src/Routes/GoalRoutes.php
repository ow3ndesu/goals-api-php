<?php
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\DB;
use App\Helpers;

return function (App $app, $authMiddleware) {

    // GET /goals
    $app->get('/goals', function (Request $req, Response $resp) {
        $user = $req->getAttribute('user');
        $query = $req->getQueryParams();
        $page = max(1, intval($query['page'] ?? 1));
        $size = min(100, max(1, intval($query['page_size'] ?? 10)));
        $offset = ($page - 1) * $size;

        $pdo = DB::get();
        $stmt = $pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id,title,target_amount,saved_amount,created_at,updated_at
                               FROM goals WHERE user_id=:uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':uid', $user['id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $goals = $stmt->fetchAll();
        $total = DB::get()->query("SELECT FOUND_ROWS() total")->fetch()['total'];

        return Helpers::jsonSuccess($resp, [
            'data'=>$goals,
            'pagination'=>['page'=>$page,'page_size'=>$size,'total'=>(int)$total]
        ]);
    })->add($authMiddleware);

    // POST /goals
    $app->post('/goals', function (Request $req, Response $resp) {
        $user = $req->getAttribute('user');
        $b = $req->getParsedBody();

        $title = trim($b['title'] ?? '');
        $target = $b['target_amount'] ?? null;
        $saved  = $b['saved_amount'] ?? 0;

        $errors=[];
        if ($title==='') $errors['title']='Required';
        if (!is_numeric($target)||$target<0) $errors['target_amount']='Non-negative number required';
        if (!is_numeric($saved)||$saved<0) $errors['saved_amount']='Non-negative number required';
        if ($errors) return Helpers::jsonError($resp,'Validation failed',422,$errors);

        $pdo=DB::get();
        $stmt=$pdo->prepare("INSERT INTO goals (user_id,title,target_amount,saved_amount) VALUES (:u,:t,:ta,:sa)");
        $stmt->execute([':u'=>$user['id'],':t'=>$title,':ta'=>$target,':sa'=>$saved]);
        $id=$pdo->lastInsertId();
        $goal=$pdo->query("SELECT id,title,target_amount,saved_amount,created_at,updated_at FROM goals WHERE id=$id")->fetch();

        return Helpers::jsonSuccess($resp,['goal'=>$goal],201);
    })->add($authMiddleware);

    // GET /goals/{id}
    $app->get('/goals/{id}', function (Request $req, Response $resp, $a) {
        $user=$req->getAttribute('user'); $id=(int)$a['id'];
        $pdo=DB::get();
        $st=$pdo->prepare("SELECT id,title,target_amount,saved_amount,created_at,updated_at FROM goals WHERE id=:id AND user_id=:u");
        $st->execute([':id'=>$id,':u'=>$user['id']]); $goal=$st->fetch();
        if(!$goal) return Helpers::jsonError($resp,'Goal not found',404);
        return Helpers::jsonSuccess($resp,['goal'=>$goal]);
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
    $app->delete('/goals/{id}', function (Request $req, Response $resp, $a) {
        $user=$req->getAttribute('user'); $id=(int)$a['id'];
        $pdo=DB::get();
        $st=$pdo->prepare("DELETE FROM goals WHERE id=:id AND user_id=:u");
        $st->execute([':id'=>$id,':u'=>$user['id']]);
        if(!$st->rowCount()) return Helpers::jsonError($resp,'Not found',404);
        return Helpers::jsonSuccess($resp,['message'=>'Deleted']);
    })->add($authMiddleware);

};
