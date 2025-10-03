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

}
