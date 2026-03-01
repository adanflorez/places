<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/jwt.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;
if ($_SERVER['REQUEST_METHOD'] !== 'POST') response(405, ['error' => 'Método no permitido']);

$body = json_decode(file_get_contents('php://input'), true);

if (empty($body['email']) || empty($body['password'])) {
    response(400, ['error' => 'Email y contraseña son requeridos']);
}

$db   = getDB();
$stmt = $db->prepare('SELECT id, name, email, password, role FROM users WHERE email = ? AND is_active = 1');
$stmt->execute([$body['email']]);
$user = $stmt->fetch();

if (!$user || !password_verify($body['password'], $user['password'])) {
    response(401, ['error' => 'Credenciales incorrectas']);
}

$token = JWT::generate([
    'sub'  => $user['id'],
    'name' => $user['name'],
    'role' => $user['role'],
]);

response(200, [
    'token' => $token,
    'user'  => [
        'id'   => $user['id'],
        'name' => $user['name'],
        'role' => $user['role'],
    ],
]);
