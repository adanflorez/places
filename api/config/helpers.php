<?php
require_once __DIR__ . '/config.php';
require_once __DIR__ . '/jwt.php';

function response(int $code, array $data): void {
    http_response_code($code);
    echo json_encode($data);
    exit;
}

function authRequired(): array {
    $headers = getallheaders();
    $auth    = $headers['Authorization'] ?? '';

    if (!str_starts_with($auth, 'Bearer ')) {
        response(401, ['error' => 'Token requerido']);
    }

    $token   = substr($auth, 7);
    $payload = JWT::verify($token);

    if (!$payload) {
        response(401, ['error' => 'Token inválido o expirado']);
    }

    return $payload;
}
