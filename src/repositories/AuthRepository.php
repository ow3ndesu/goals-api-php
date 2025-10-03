<?php

namespace App\Repositories;

use PDO;
use DateTime;
use DateInterval;

use Firebase\JWT\JWT;

class AuthRepository {
    private PDO $pdo;
    public function __construct(PDO $pdo) { $this->pdo = $pdo; }

    function generateAccessToken(array $user) {
        $now = time();
        $ttl = intval($_ENV['ACCESS_TOKEN_TTL'] ?: 900);
        $payload = [
            'iss' => $_ENV['JWT_ISSUER'] ?: 'goals-api',
            'aud' => $_ENV['JWT_AUDIENCE'] ?: 'goals-api',
            'iat' => $now,
            'nbf' => $now,
            'exp' => $now + $ttl,
            'sub' => $user['id'],
            'email' => $user['email']
        ];
        return JWT::encode($payload, $_ENV['JWT_SECRET'], 'HS256');
    }

    function checkRateLimit(string $ip, string $endpoint, int $limit = 5, int $windowSeconds = 900): bool {
        // Fetch attempts, last_attempt and DB-computed diff (seconds since last_attempt according to DB clock)
        $stmt = $this->pdo->prepare("
            SELECT attempts,
                last_attempt,
                (UNIX_TIMESTAMP(NOW()) - UNIX_TIMESTAMP(last_attempt)) AS diff
            FROM rate_limits
            WHERE ip = :ip AND endpoint = :ep
            LIMIT 1
        ");
        $stmt->execute([':ip' => $ip, ':ep' => $endpoint]);
        $row = $stmt->fetch(PDO::FETCH_ASSOC);

        // If no row yet -> create it and allow
        if (!$row) {
            $ins = $this->pdo->prepare("INSERT INTO rate_limits (ip, endpoint, attempts, last_attempt) VALUES (:ip, :ep, 1, NOW())");
            $ins->execute([':ip' => $ip, ':ep' => $endpoint]);
            return true;
        }

        $diff = isset($row['diff']) ? (int)$row['diff'] : null;

        // If diff is NULL or negative, normalize: set last_attempt = NOW() and allow.
        // Negative diff means DB thinks last_attempt is in future relative to DB NOW() — rare,
        // but we guard: reset the row so logic continues consistently.
        if ($diff === null || $diff < 0) {
            $upd = $this->pdo->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = NOW() WHERE ip = :ip AND endpoint = :ep");
            $upd->execute([':ip' => $ip, ':ep' => $endpoint]);
            return true;
        }

        // If the last attempt was older than the window -> reset attempts and allow
        if ($diff >= $windowSeconds) {
            $upd = $this->pdo->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = NOW() WHERE ip = :ip AND endpoint = :ep");
            $upd->execute([':ip' => $ip, ':ep' => $endpoint]);
            return true;
        }

        // Still inside window: if attempts already >= limit -> block
        if ((int)$row['attempts'] >= $limit) {
            return false;
        }

        // Otherwise increment attempts and allow
        $upd = $this->pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE ip = :ip AND endpoint = :ep");
        $upd->execute([':ip' => $ip, ':ep' => $endpoint]);
        return true;
    }

    public function login(array $body, string $ip): ?array {
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';

        if (!$email || !$password) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'Email and password required',
            ];
        }

        // 🔒 Rate limiting check
        if (!$this->checkRateLimit($ip, '/auth/login', intval($_ENV['LIMIT']), intval($_ENV['WINDOW_SECONDS']))) {
            return [
                'error' => true,
                'code' => 429,
                'message' => 'Too many login attempts. Try again later.',
            ];
        }
        
        $stmt = $this->pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return [
                'error' => true,
                'code' => 401,
                'message' => 'Invalid credentials',
            ];
        }

        $access = $this->generateAccessToken($user);

        // Issue refresh token
        $refresh = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime())->add(new DateInterval('P14D'))->format('Y-m-d H:i:s');
        $ins = $this->pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (:uid, :token, :exp)");
        $ins->execute([':uid' => $user['id'], ':token' => hash('sha256', $refresh), ':exp' => $expiresAt]);

        return [
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => intval($_ENV['ACCESS_TOKEN_TTL'] ?: 900),
            'refresh_token' => $refresh
        ];
    }

    public function refreshToken(array $body): ?array {
        $refresh = $body['refresh_token'] ?? '';
        
        if (!$refresh) {
            return [
                'error' => true,
                'code' => 400,
                'message' => 'refresh_token required',
            ];
        }

        $hashed = hash('sha256', $refresh);
        $stmt = $this->pdo->prepare("SELECT rt.*, u.id as uid, u.email FROM refresh_tokens rt JOIN users u ON u.id = rt.user_id WHERE rt.token = :tok");
        $stmt->execute([':tok' => $hashed]);
        $row = $stmt->fetch();

        if (!$row) {
            return [
                'error' => true,
                'code' => 401,
                'message' => 'Invalid refresh token',
            ];
        }

        if (new DateTime($row['expires_at']) < new DateTime()) {
            return [
                'error' => true,
                'code' => 401,
                'message' => 'Refresh token expired',
            ];
        }

        // Rotate token
        $this->pdo->prepare("DELETE FROM refresh_tokens WHERE id = :id")->execute([':id' => $row['id']]);
        $newRefresh = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime())->add(new DateInterval('P14D'))->format('Y-m-d H:i:s');
        $this->pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (:uid, :token, :exp)")
            ->execute([':uid'=>$row['user_id'], ':token'=>hash('sha256',$newRefresh), ':exp'=>$expiresAt]);

        $user = ['id' => $row['user_id'], 'email' => $row['email']];
        $access = $this->generateAccessToken($user);

        return [
            'access_token' => $access,
            'token_type' => 'Bearer',
            'expires_in' => intval($_ENV['ACCESS_TOKEN_TTL'] ?: 900),
            'refresh_token' => $newRefresh
        ];
    }
}