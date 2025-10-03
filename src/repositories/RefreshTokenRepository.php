<?php

namespace App\Repositories;

use PDO;
use DateTime;
use DateInterval;

class RefreshTokenRepository {
    private PDO $pdo;

    public function __construct(PDO $pdo) {
        $this->pdo = $pdo;
    }

    public function findValidToken(string $hashedToken): ?array {
        $stmt = $this->pdo->prepare("
            SELECT user_id, token, expires_at 
            FROM refresh_tokens 
            WHERE token = :token AND expires_at > NOW()
        ");
        $stmt->execute([':token' => $hashedToken]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        return $row ?: null;
    }

    public function storeToken(int $userId, string $refreshToken): void {
        $hashed = hash('sha256', $refreshToken);
        $expiresAt = (new DateTime())->add(new DateInterval('P14D'))->format('Y-m-d H:i:s');

        $stmt = $this->pdo->prepare("
            INSERT INTO refresh_tokens (user_id, token, expires_at) 
            VALUES (:uid, :token, :exp)
        ");
        $stmt->execute([
            ':uid' => $userId,
            ':token' => $hashed,
            ':exp' => $expiresAt
        ]);
    }

    public function deleteToken(string $hashedToken): void {
        $stmt = $this->pdo->prepare("DELETE FROM refresh_tokens WHERE token = :token");
        $stmt->execute([':token' => $hashedToken]);
    }
}
