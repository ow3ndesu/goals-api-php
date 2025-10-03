<?php

namespace App\Repositories;

use PDO;

class GoalRepository {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getGoals(array $user, array $query): ?array {
        $page = max(1, intval($query['page'] ?? 1));
        $size = min(100, max(1, intval($query['page_size'] ?? 10)));
        $offset = ($page - 1) * $size;

        $stmt = $this->pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id,title,target_amount,saved_amount,created_at,updated_at
                               FROM goals WHERE user_id=:uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':uid', $user['id'], PDO::PARAM_INT);
        $stmt->bindValue(':limit', $size, PDO::PARAM_INT);
        $stmt->bindValue(':offset', $offset, PDO::PARAM_INT);
        $stmt->execute();
        $goals = $stmt->fetchAll();

        $total = $this->pdo->query("SELECT FOUND_ROWS() total")->fetch()['total'];

        return [
            'data' => $goals,
            'pagination' => [
                'page' => $page,
                'page_size' => $size,
                'total' => (int)$total
            ]
        ];
    }

    public function addGoal(array $user, array $body): ?array {
        $title = trim($body['title'] ?? '');
        $target = $body['target_amount'] ?? null;
        $saved  = $body['saved_amount'] ?? 0;

        $errors = [];
        if ($title === '') $errors['title'] = 'Required';
        if (!is_numeric($target) || $target < 0) $errors['target_amount'] = 'Non-negative number required';
        if (!is_numeric($saved) || $saved < 0) $errors['saved_amount'] = 'Non-negative number required';
        
        if ($errors) {
            return [
                'error' => true,
                'code' => 422,
                'message' => 'Validation failed',
                'erros' => $errors
            ];
        }

        $stmt = $this->pdo->prepare("INSERT INTO goals (user_id, title, target_amount, saved_amount) VALUES (:u, :t, :ta, :sa)");
        $stmt->execute([':u' => $user['id'], ':t' => $title, ':ta' => $target, ':sa' => $saved]);
        $id = $this->pdo->lastInsertId();
        $goal = $this->pdo->query("SELECT id, title, target_amount, saved_amount, created_at, updated_at FROM goals WHERE id = $id")->fetch();

        return [
            'goal' => $goal
        ];
    }

    public function getGoal(array $user, int $id): ?array {
        $stmt = $this->pdo->prepare("SELECT id, title, target_amount, saved_amount, created_at, updated_at FROM goals WHERE id = :id AND user_id = :u");
        $stmt->execute([':id' => $id, ':u' => $user['id']]);
        $goal=$stmt->fetch();
        
        if (!$goal) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Goal not found',
            ];
        }

        return [
            'goal' => $goal
        ];
    }

    public function updateGoal(array $user, array $body, $id): ?array {
        $title = $body['title'] ?? null;
        $target = $body['target_amount'] ?? null;
        $saved = $body['saved_amount'] ?? null;

        $errors = [];
        if ($title !== null && trim($title)==='') $errors['title'] = 'Cannot be empty';
        if ($target !== null && (!is_numeric($target) || $target < 0)) $errors['target_amount']='Must be non-negative';
        if ($saved !== null && (!is_numeric($saved) || $saved < 0)) $errors['saved_amount']='Must be non-negative';

        if ($errors) {
            return [
                'error' => true,
                'code' => 422,
                'message' => 'Validation failed',
                'erros' => $errors
            ];
        }

        $check=$this->pdo->prepare("SELECT id FROM goals WHERE id=:id AND user_id=:u");
        $check->execute([':id' => $id,':u' => $user['id']]);
        
        if (!$check->fetch()) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Not found',
            ];
        }

        $fields = [];
        $p = [':id' => $id];

        if ($title !== null) {
            $fields[] = "title = :t";
            $p[':t'] = trim($title);
        }

        if ($target !== null) {
            $fields[] = "target_amount = :ta";
            $p[':ta'] = $target;
        }

        if ($saved !== null) {
            $fields[] = "saved_amount = :sa";
            $p[':sa'] = $saved;
        }

        if (!$fields) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No fields',
            ];
        }

        $this->pdo->prepare("UPDATE goals SET ". implode(',',$fields). " WHERE id = :id")->execute($p);
        $goal = $this->pdo->query("SELECT id, title, target_amount, saved_amount, created_at, updated_at FROM goals WHERE id = $id")->fetch();
        
        return [
            'goal' => $goal
        ];
    }

    public function updateGoalPartially(array $user, array $fields, $id): ?array {
        if (empty($fields)) {
            return [
                'error' => true,
                'code' => 422,
                'message' => 'Validation failed',
            ];
        }

        $check = $this->pdo->prepare("SELECT id FROM goals WHERE id = :id AND user_id = :u");
        $check->execute([':id' => $id, ':u' => $user['id']]);
        
        if (!$check->fetch()) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Not found',
            ];
        }

        $set = implode(", ", array_map(fn($f) => "$f = :$f", array_keys($fields)));
        $fields['id'] = $id;
        $fields['uid'] = $user['id'];

        $sql = "UPDATE goals SET $set WHERE id = :id AND user_id = :uid";
        $stmt = $this->pdo->prepare($sql);
        $stmt->execute($fields);

        if ($stmt->rowCount() === 0) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Nothing is updated',
            ];
        }
        
        $goal = $this->pdo->query("SELECT id, title, target_amount, saved_amount, created_at, updated_at FROM goals WHERE id = $id")->fetch();
        
        return [
            'goal' => $goal
        ];
    }

    public function deleteGoal(array $user, int $id): ?array {
        $stmt = $this->pdo->prepare("DELETE FROM goals WHERE id = :id AND user_id = :u");
        $stmt->execute([':id' => $id, ':u' => $user['id']]);
        
        if (!$stmt->rowCount()) {
            return [
                'error' => true,
                'code' => 404,
                'message' => 'Goal not found',
            ];
        }

        return [
            'message' => 'Deleted'
        ];
    }
}
