<?php

namespace App\Services;

use PDO;

use App\Repositories\GoalRepository;

class GoalService {
    private GoalRepository $repo;
    public function __construct(PDO $pdo) {
        $this->repo = new GoalRepository($pdo);
    }

    public function getGoals(array $user, array $query): ?array {
        return $this->repo->getGoals($user, $query);
    }

    public function addGoal(array $user, array $body): ?array {
        return $this->repo->addGoal($user, $body);
    }

    public function getGoal(array $user, int $id): ?array {
        return $this->repo->getGoal($user, $id);
    }

    public function updateGoal(array $user, array $body, int $id): ?array {
        return $this->repo->updateGoal($user, $body, $id);
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

        return $this->repo->updateGoalPartially($user, $fields, $id);
    }

    public function deleteGoal(array $user, int $id): ?array {
        return $this->repo->deleteGoal($user, $id);
    }
}
