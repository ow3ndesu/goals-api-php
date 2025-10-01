<?php
namespace App;

use PDO;
use DateTime;

class Helpers {
    public static function jsonError($response, $message, $status = 400, $errors = null) {
        $payload = [
            'error' => true,
            'message' => $message
        ];
        if ($errors !== null) $payload['errors'] = $errors;
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type','application/json')->withStatus($status);
    }

    public static function jsonSuccess($response, $data, $status = 200) {
        $payload = array_merge(['error' => false], $data);
        $response->getBody()->write(json_encode($payload));
        return $response->withHeader('Content-Type','application/json')->withStatus($status);
    }

    public static function checkRateLimit(PDO $pdo, string $ip, string $endpoint, int $limit = 5, int $windowSeconds = 900): bool {
        // Fetch attempts, last_attempt and DB-computed diff (seconds since last_attempt according to DB clock)
        $stmt = $pdo->prepare("
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
            $ins = $pdo->prepare("INSERT INTO rate_limits (ip, endpoint, attempts, last_attempt) VALUES (:ip, :ep, 1, NOW())");
            $ins->execute([':ip' => $ip, ':ep' => $endpoint]);
            return true;
        }

        $diff = isset($row['diff']) ? (int)$row['diff'] : null;

        // If diff is NULL or negative, normalize: set last_attempt = NOW() and allow.
        // Negative diff means DB thinks last_attempt is in future relative to DB NOW() — rare,
        // but we guard: reset the row so logic continues consistently.
        if ($diff === null || $diff < 0) {
            $upd = $pdo->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = NOW() WHERE ip = :ip AND endpoint = :ep");
            $upd->execute([':ip' => $ip, ':ep' => $endpoint]);
            return true;
        }

        // If the last attempt was older than the window -> reset attempts and allow
        if ($diff >= $windowSeconds) {
            $upd = $pdo->prepare("UPDATE rate_limits SET attempts = 1, last_attempt = NOW() WHERE ip = :ip AND endpoint = :ep");
            $upd->execute([':ip' => $ip, ':ep' => $endpoint]);
            return true;
        }

        // Still inside window: if attempts already >= limit -> block
        if ((int)$row['attempts'] >= $limit) {
            return false;
        }

        // Otherwise increment attempts and allow
        $upd = $pdo->prepare("UPDATE rate_limits SET attempts = attempts + 1, last_attempt = NOW() WHERE ip = :ip AND endpoint = :ep");
        $upd->execute([':ip' => $ip, ':ep' => $endpoint]);
        return true;
    }

}
