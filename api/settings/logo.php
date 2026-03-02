<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

authRequired();

if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    response(405, ['error' => 'Método no permitido']);
}

$file = $_FILES['logo'] ?? null;
if (!$file || $file['error'] !== UPLOAD_ERR_OK) {
    response(400, ['error' => 'No se recibió ningún archivo']);
}

$allowed = ['image/jpeg', 'image/png', 'image/webp', 'image/svg+xml'];
if (!in_array($file['type'], $allowed)) {
    response(400, ['error' => 'Tipo de archivo no permitido. Usa JPG, PNG, WebP o SVG']);
}

if ($file['size'] > 2 * 1024 * 1024) {
    response(400, ['error' => 'El archivo no debe superar 2MB']);
}

$ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
$filename = 'logo-' . uniqid() . '.' . $ext;
$dir      = __DIR__ . '/../../uploads/logos/';
$dest     = $dir . $filename;

if (!is_dir($dir)) mkdir($dir, 0775, true);

if (!move_uploaded_file($file['tmp_name'], $dest)) {
    response(500, ['error' => 'Error al guardar el archivo']);
}

$url = 'uploads/logos/' . $filename;

// Guardar en settings
$db = getDB();
$db->prepare("UPDATE settings SET `value` = ? WHERE `key` = 'site_logo'")->execute([$url]);

response(200, ['url' => $url]);
