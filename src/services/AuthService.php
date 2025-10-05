<?php

namespace App\Services;

use PDO;

use Firebase\JWT\JWT;
use Firebase\JWT\Key;

use App\Repositories\AuthRepository;

class AuthService {
    private AuthRepository $repo;
    public function __construct(PDO $pdo) {
        $this->repo = new AuthRepository($pdo);
    }

    public function validateAccessToken(string $token): ?array {
        try {
            $decoded = JWT::decode($token, new Key($_ENV['JWT_SECRET'], 'HS256'));
            return (array) $decoded;
        } catch (\Firebase\JWT\ExpiredException $e) {
            return null; // Expired
        } catch (\Exception $e) {
            return null; // Invalid
        }
    }

    public function login(array $body, string $ip): ?array {
        return $this->repo->login($body, $ip);
    }

    public function refreshTokens(string $refreshToken): ?array {
        return $this->repo->refreshTokens($refreshToken);
    }
}
