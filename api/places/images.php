<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: POST, DELETE, PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

authRequired();

$method = $_SERVER['REQUEST_METHOD'];
$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;

match ($method) {
    'POST'   => uploadImage(),
    'DELETE' => deleteImage($id),
    'PUT'    => setCover($id),
    default  => response(405, ['error' => 'Método no permitido']),
};

// ============================================================

function uploadImage(): void {
    $placeId = isset($_POST['place_id']) ? (int)$_POST['place_id'] : 0;
    if (!$placeId) response(400, ['error' => 'place_id es requerido']);

    // Verificar que el lugar existe
    $db   = getDB();
    $stmt = $db->prepare('SELECT id FROM places WHERE id = ?');
    $stmt->execute([$placeId]);
    if (!$stmt->fetch()) response(404, ['error' => 'Lugar no encontrado']);

    if (empty($_FILES['image'])) response(400, ['error' => 'No se envió ninguna imagen']);

    $file     = $_FILES['image'];
    $allowed  = ['image/jpeg', 'image/png', 'image/webp'];
    $maxSize  = 5 * 1024 * 1024; // 5 MB

    if ($file['error'] !== UPLOAD_ERR_OK) {
        response(400, ['error' => 'Error al subir el archivo']);
    }
    if (!in_array($file['type'], $allowed)) {
        response(400, ['error' => 'Formato no permitido. Usa JPG, PNG o WebP']);
    }
    if ($file['size'] > $maxSize) {
        response(400, ['error' => 'La imagen no debe superar 5 MB']);
    }

    // Nombre único para evitar colisiones
    $ext      = pathinfo($file['name'], PATHINFO_EXTENSION);
    $filename = "place-{$placeId}-" . uniqid() . '.' . strtolower($ext);
    $destDir  = __DIR__ . '/../../uploads/places/';
    $destPath = $destDir . $filename;
    $url      = 'uploads/places/' . $filename;

    if (!move_uploaded_file($file['tmp_name'], $destPath)) {
        response(500, ['error' => 'No se pudo guardar la imagen en el servidor']);
    }

    // ¿Es la primera imagen? → portada automática
    $stmt = $db->prepare('SELECT COUNT(*) FROM place_images WHERE place_id = ?');
    $stmt->execute([$placeId]);
    $isCover = (int)$stmt->fetchColumn() === 0 ? 1 : 0;

    // Obtener el siguiente sort_order
    $stmt = $db->prepare('SELECT COALESCE(MAX(sort_order), 0) + 1 FROM place_images WHERE place_id = ?');
    $stmt->execute([$placeId]);
    $sortOrder = (int)$stmt->fetchColumn();

    $stmt = $db->prepare('INSERT INTO place_images (place_id, url, is_cover, sort_order) VALUES (?, ?, ?, ?)');
    $stmt->execute([$placeId, $url, $isCover, $sortOrder]);

    response(201, [
        'id'       => (int)$db->lastInsertId(),
        'url'      => $url,
        'is_cover' => $isCover,
        'message'  => 'Imagen subida correctamente',
    ]);
}

function deleteImage(int $id): void {
    if (!$id) response(400, ['error' => 'id es requerido']);

    $db   = getDB();
    $stmt = $db->prepare('SELECT url, place_id, is_cover FROM place_images WHERE id = ?');
    $stmt->execute([$id]);
    $img  = $stmt->fetch();

    if (!$img) response(404, ['error' => 'Imagen no encontrada']);

    // Borrar archivo físico
    $filePath = __DIR__ . '/../../' . $img['url'];
    if (file_exists($filePath)) unlink($filePath);

    $db->prepare('DELETE FROM place_images WHERE id = ?')->execute([$id]);

    // Si era la portada, asignar la siguiente imagen como portada
    if ($img['is_cover']) {
        $stmt = $db->prepare('SELECT id FROM place_images WHERE place_id = ? ORDER BY sort_order LIMIT 1');
        $stmt->execute([$img['place_id']]);
        $next = $stmt->fetch();
        if ($next) {
            $db->prepare('UPDATE place_images SET is_cover = 1 WHERE id = ?')->execute([$next['id']]);
        }
    }

    response(200, ['message' => 'Imagen eliminada correctamente']);
}

function setCover(int $id): void {
    if (!$id) response(400, ['error' => 'id es requerido']);

    $db   = getDB();
    $stmt = $db->prepare('SELECT place_id FROM place_images WHERE id = ?');
    $stmt->execute([$id]);
    $img  = $stmt->fetch();

    if (!$img) response(404, ['error' => 'Imagen no encontrada']);

    // Quitar portada anterior y asignar la nueva
    $db->prepare('UPDATE place_images SET is_cover = 0 WHERE place_id = ?')->execute([$img['place_id']]);
    $db->prepare('UPDATE place_images SET is_cover = 1 WHERE id = ?')->execute([$id]);

    response(200, ['message' => 'Portada actualizada correctamente']);
}
