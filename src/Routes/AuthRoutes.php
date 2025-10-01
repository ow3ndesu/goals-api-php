<?php
use Slim\App;
use Slim\Psr7\Request;
use Slim\Psr7\Response;
use App\DB;
use App\Helpers;
use Firebase\JWT\JWT;

return function (App $app, $authMiddleware) {

    // Generate access token helper
    function generateAccessToken($user) {
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

    // POST /auth/login
    $app->post('/auth/login', function (Request $req, Response $resp) {
        $body = $req->getParsedBody();
        $email = trim($body['email'] ?? '');
        $password = $body['password'] ?? '';
        $ip = $req->getServerParams()['REMOTE_ADDR'] ?? 'unknown';

        if (!$email || !$password) {
            return Helpers::jsonError($resp, 'Email and password required', 400);
        }

        $pdo = DB::get();
        $stmt = $pdo->prepare("SELECT id, email, password_hash FROM users WHERE email = :email");
        $stmt->execute([':email' => $email]);
        $user = $stmt->fetch();

        if (!$user || !password_verify($password, $user['password_hash'])) {
            return Helpers::jsonError($resp, 'Invalid credentials', 401);
        }

        $access = generateAccessToken($user);

        // Issue refresh token
        $refresh = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime())->add(new DateInterval('P14D'))->format('Y-m-d H:i:s');
        $ins = $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (:uid, :token, :exp)");
        $ins->execute([':uid' => $user['id'], ':token' => hash('sha256', $refresh), ':exp' => $expiresAt]);

        return Helpers::jsonSuccess($resp, [
            'access_token' => $access,
            'token_type'   => 'Bearer',
            'expires_in'   => intval($_ENV['ACCESS_TOKEN_TTL'] ?: 900),
            'refresh_token'=> $refresh
        ]);
    });

    // POST /auth/refresh
    $app->post('/auth/refresh', function (Request $req, Response $resp) {
        $body = $req->getParsedBody();
        $refresh = $body['refresh_token'] ?? '';
        if (!$refresh) return Helpers::jsonError($resp, 'refresh_token required', 400);

        $hashed = hash('sha256', $refresh);
        $pdo = DB::get();
        $stmt = $pdo->prepare("SELECT rt.*, u.id as uid, u.email FROM refresh_tokens rt JOIN users u ON u.id = rt.user_id WHERE rt.token = :tok");
        $stmt->execute([':tok' => $hashed]);
        $row = $stmt->fetch();

        if (!$row) return Helpers::jsonError($resp, 'Invalid refresh token', 401);

        if (new DateTime($row['expires_at']) < new DateTime()) {
            return Helpers::jsonError($resp, 'Refresh token expired', 401);
        }

        // Rotate token
        $pdo->prepare("DELETE FROM refresh_tokens WHERE id = :id")->execute([':id' => $row['id']]);
        $newRefresh = bin2hex(random_bytes(32));
        $expiresAt = (new DateTime())->add(new DateInterval('P14D'))->format('Y-m-d H:i:s');
        $pdo->prepare("INSERT INTO refresh_tokens (user_id, token, expires_at) VALUES (:uid, :token, :exp)")
            ->execute([':uid'=>$row['user_id'], ':token'=>hash('sha256',$newRefresh), ':exp'=>$expiresAt]);

        $user = ['id'=>$row['user_id'], 'email'=>$row['email']];
        $access = generateAccessToken($user);

        return Helpers::jsonSuccess($resp, [
            'access_token'=>$access,
            'token_type'=>'Bearer',
            'expires_in'=>intval($_ENV['ACCESS_TOKEN_TTL'] ?: 900),
            'refresh_token'=>$newRefresh
        ]);
    });
};
