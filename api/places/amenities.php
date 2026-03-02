<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$db     = getDB();
$method = $_SERVER['REQUEST_METHOD'];

// GET — lista todas las amenidades
if ($method === 'GET') {
    $rows = $db->query("SELECT id, name, icon FROM amenities ORDER BY name")->fetchAll();
    response(200, $rows);
}

// POST — crear nueva amenidad (protegido)
if ($method === 'POST') {
    authRequired();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];
    $name = trim($data['name'] ?? '');
    $icon = trim($data['icon'] ?? 'fa fa-star');
    if (!$name) response(400, ['error' => 'El nombre es requerido']);

    $stmt = $db->prepare("INSERT INTO amenities (name, icon) VALUES (?, ?)");
    $stmt->execute([$name, $icon]);
    $id = $db->lastInsertId();
    response(201, ['id' => (int)$id, 'name' => $name, 'icon' => $icon]);
}

// DELETE ?id= — eliminar amenidad (protegido)
if ($method === 'DELETE') {
    authRequired();
    $id = (int)($_GET['id'] ?? 0);
    if (!$id) response(400, ['error' => 'ID requerido']);
    $db->prepare("DELETE FROM amenities WHERE id = ?")->execute([$id]);
    response(200, ['message' => 'Amenidad eliminada']);
}

response(405, ['error' => 'Método no permitido']);
