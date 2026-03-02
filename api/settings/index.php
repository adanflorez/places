<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$method = $_SERVER['REQUEST_METHOD'];
$db     = getDB();

// GET — público: devuelve todas las settings como objeto key→value
if ($method === 'GET') {
    $rows = $db->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    response(200, $rows);
}

// PUT — protegido: actualiza una o más settings
if ($method === 'PUT') {
    authRequired();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $allowed = ['site_name','site_logo','site_description','whatsapp',
                'social_facebook','social_instagram','social_twitter','social_telegram'];

    $stmt = $db->prepare("UPDATE settings SET `value` = ? WHERE `key` = ?");
    foreach ($allowed as $key) {
        if (array_key_exists($key, $data)) {
            $stmt->execute([trim($data[$key]), $key]);
        }
    }

    $rows = $db->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    response(200, ['message' => 'Configuración guardada', 'settings' => $rows]);
}

response(405, ['error' => 'Método no permitido']);

// GET — público: devuelve todas las settings como objeto key→value
if ($method === 'GET') {
    $rows = $db->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    response(200, $rows);
}

// PUT — protegido: actualiza una o más settings
if ($method === 'PUT') {
    authRequired();
    $data = json_decode(file_get_contents('php://input'), true) ?? [];

    $allowed = ['site_name','site_logo','site_description','whatsapp',
                'social_facebook','social_instagram','social_twitter','social_telegram'];

    $stmt = $db->prepare("UPDATE settings SET `value` = ? WHERE `key` = ?");
    foreach ($allowed as $key) {
        if (array_key_exists($key, $data)) {
            $stmt->execute([trim($data[$key]), $key]);
        }
    }

    $rows = $db->query("SELECT `key`, `value` FROM settings")->fetchAll(PDO::FETCH_KEY_PAIR);
    response(200, ['message' => 'Configuración guardada', 'settings' => $rows]);
}

response(405, ['error' => 'Método no permitido']);
