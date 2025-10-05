<?php

namespace App\Services;

use PDO;

use App\Repositories\GoalRepository;
use LDAP\Result;

class GoalService {
    private GoalRepository $repo;
    public function __construct(PDO $pdo) {
        $this->repo = new GoalRepository($pdo);
    }

    public function getGoals(array $user, array $query): ?array {
        $userID = $user['id'];
        $page = max(1, intval($query['page'] ?? 1));
        $size = min(100, max(1, intval($query['page_size'] ?? 10)));
        $offset = ($page - 1) * $size;

        return $this->repo->getGoals($userID, $page, $size, $offset);
    }

    public function addGoal(array $user, array $body): ?array {
        $userID = $user['id'];
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

        return $this->repo->addGoal($userID, $title, $target, $saved);
    }

    public function getGoal(array $user, int $id): ?array {
        return $this->repo->getGoal($user, $id);
    }

    public function updateGoal(array $user, array $body, int $id): ?array {
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

        $result = $this->getGoal($user, $id);

        if (isset($result['error']) && $result['error']) {
            return $result; 
        }

        $fields = [];
        $values = [':id' => $id];

        if ($title !== null) {
            $fields[] = "title = :t";
            $values[':t'] = trim($title);
        }

        if ($target !== null) {
            $fields[] = "target_amount = :ta";
            $values[':ta'] = $target;
        }

        if ($saved !== null) {
            $fields[] = "saved_amount = :sa";
            $values[':sa'] = $saved;
        }

        if (!$fields) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'No fields',
            ];
        }

        return $this->repo->updateGoal($fields, $values, $id);
    }

    public function updateGoalPartially(array $user, array $body, int $id): ?array {
        $allowed = ['title', 'target_amount', 'saved_amount'];
        $fields = array_intersect_key($body, array_flip($allowed));

        if (isset($fields['saved_amount'], $fields['target_amount']) &&
            $fields['saved_amount'] > $fields['target_amount']) {
            return [
                'error' => true,
                'code' => 422,
                'message' => 'Validation failed',
            ];
        }

        if (empty($fields)) {
            return [
                'error' => true,
                'code' => 422,
                'message' => 'Validation failed',
            ];
        }

        $result = $this->getGoal($user, $id);

        if (isset($result['error']) && $result['error']) {
            return $result; 
        }

        $set = implode(", ", array_map(fn($f) => "$f = :$f", array_keys($fields)));
        $fields['id'] = $id;
        $fields['uid'] = $user['id'];

        return $this->repo->updateGoalPartially($set, $fields, $id);
    }

    public function deleteGoal(array $user, int $id): ?array {
        $userID = $user['id'];

        $result = $this->getGoal($user, $id);

        if (isset($result['error']) && $result['error']) {
            return $result; 
        }

        return $this->repo->deleteGoal($userID, $id);
    }
}
