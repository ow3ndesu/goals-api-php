<?php

namespace App\Repositories;

use PDO;

class GoalRepository {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    public function getGoals(int $userID, int $page, int $size, int $offset): ?array {
        $stmt = $this->pdo->prepare("SELECT SQL_CALC_FOUND_ROWS id,title,target_amount,saved_amount,created_at,updated_at
                               FROM goals WHERE user_id=:uid ORDER BY created_at DESC LIMIT :limit OFFSET :offset");
        $stmt->bindValue(':uid', $userID, PDO::PARAM_INT);
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

    public function addGoal(int $userID, string $title, int $target, int $saved): ?array {
        $stmt = $this->pdo->prepare("INSERT INTO goals (user_id, title, target_amount, saved_amount) VALUES (:u, :t, :ta, :sa)");
        $stmt->execute([':u' => $userID, ':t' => $title, ':ta' => $target, ':sa' => $saved]);
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

    public function updateGoal(array $fields, array $values, int $id): ?array {
        $this->pdo->prepare("UPDATE goals SET ". implode(',', $fields). " WHERE id = :id")->execute($values);
        $goal = $this->pdo->query("SELECT id, title, target_amount, saved_amount, created_at, updated_at FROM goals WHERE id = $id")->fetch();
        
        return [
            'goal' => $goal
        ];
    }

    public function updateGoalPartially(string $set, array $fields, int $id): ?array {
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

    public function deleteGoal(int $userID, int $id): ?array {
        $stmt = $this->pdo->prepare("DELETE FROM goals WHERE id = :id AND user_id = :u");
        $stmt->execute([':id' => $id, ':u' => $userID]);

        return [
            'message' => 'Deleted'
        ];
    }
}
