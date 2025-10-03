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

    public function deleteGoal(array $user, int $id): ?array {
        return $this->repo->deleteGoal($user, $id);
    }
}
