<?php

namespace App\Services;

use PDO;

use App\Repositories\AuthRepository;

class AuthService {
    private AuthRepository $repo;
    public function __construct(PDO $pdo) {
        $this->repo = new AuthRepository($pdo);
    }

    public function login(array $body, string $ip): ?array {
        return $this->repo->login($body, $ip);
    }

    public function rotateRefreshToken(array $body): ?array {
        return $this->repo->rotateRefreshToken($body);
    }
}
