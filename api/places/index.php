<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, DELETE, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method = $_SERVER['REQUEST_METHOD'];

// -- Rutas colección: /api/places --
if (!$id) {
    match ($method) {
        'GET'  => listPlaces(),
        'POST' => createPlace(),
        default => response(405, ['error' => 'Método no permitido']),
    };
}

// -- Rutas recurso: /api/places/:id --
match ($method) {
    'GET'    => showPlace($id),
    'PUT'    => updatePlace($id),
    'DELETE' => deletePlace($id),
    default  => response(405, ['error' => 'Método no permitido']),
};

// ============================================================

function listPlaces(): void {
    $db   = getDB();
    $stmt = $db->query("
        SELECT p.id, p.name, p.slug, p.price_per_night, p.location, p.max_guests,
               i.url AS cover_image
        FROM places p
        LEFT JOIN place_images i ON i.place_id = p.id AND i.is_cover = 1
        WHERE p.is_active = 1
        ORDER BY p.created_at DESC
    ");
    response(200, $stmt->fetchAll());
}

function showPlace(int $id): void {
    $db = getDB();

    // Datos del lugar
    $stmt = $db->prepare("
        SELECT p.*, u.name AS created_by_name
        FROM places p
        JOIN users u ON u.id = p.created_by
        WHERE p.id = ? AND p.is_active = 1
    ");
    $stmt->execute([$id]);
    $place = $stmt->fetch();

    if (!$place) response(404, ['error' => 'Lugar no encontrado']);

    // Imágenes
    $stmt = $db->prepare("SELECT id, url, is_cover, sort_order FROM place_images WHERE place_id = ? ORDER BY sort_order");
    $stmt->execute([$id]);
    $place['images'] = $stmt->fetchAll();

    // Amenidades
    $stmt = $db->prepare("
        SELECT a.id, a.name, a.icon
        FROM amenities a
        JOIN place_amenities pa ON pa.amenity_id = a.id
        WHERE pa.place_id = ?
    ");
    $stmt->execute([$id]);
    $place['amenities'] = $stmt->fetchAll();

    response(200, $place);
}

function createPlace(): void {
    $user = authRequired();
    $body = json_decode(file_get_contents('php://input'), true);

    $required = ['name', 'slug', 'description', 'price_per_night', 'location', 'max_guests'];
    foreach ($required as $field) {
        if (empty($body[$field])) response(400, ['error' => "El campo '$field' es requerido"]);
    }

    $db   = getDB();
    $stmt = $db->prepare("
        INSERT INTO places (name, slug, description, price_per_night, location, max_guests, created_by)
        VALUES (?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $body['name'],
        $body['slug'],
        $body['description'],
        $body['price_per_night'],
        $body['location'],
        $body['max_guests'],
        $user['sub'],
    ]);

    $newId = (int) $db->lastInsertId();

    // Amenidades opcionales
    if (!empty($body['amenities']) && is_array($body['amenities'])) {
        $ins = $db->prepare("INSERT INTO place_amenities (place_id, amenity_id) VALUES (?, ?)");
        foreach ($body['amenities'] as $amenityId) {
            $ins->execute([$newId, (int)$amenityId]);
        }
    }

    response(201, ['id' => $newId, 'message' => 'Lugar creado correctamente']);
}

function updatePlace(int $id): void {
    authRequired();
    $body = json_decode(file_get_contents('php://input'), true);

    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM places WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) response(404, ['error' => 'Lugar no encontrado']);

    $fields = [];
    $values = [];
    $allowed = ['name', 'slug', 'description', 'price_per_night', 'location', 'max_guests', 'is_active'];

    foreach ($allowed as $field) {
        if (isset($body[$field])) {
            $fields[] = "$field = ?";
            $values[] = $body[$field];
        }
    }

    if (empty($fields)) response(400, ['error' => 'No hay campos para actualizar']);

    $values[] = $id;
    $db->prepare("UPDATE places SET " . implode(', ', $fields) . " WHERE id = ?")->execute($values);

    // Actualizar amenidades si se envían
    if (isset($body['amenities']) && is_array($body['amenities'])) {
        $db->prepare("DELETE FROM place_amenities WHERE place_id = ?")->execute([$id]);
        $ins = $db->prepare("INSERT INTO place_amenities (place_id, amenity_id) VALUES (?, ?)");
        foreach ($body['amenities'] as $amenityId) {
            $ins->execute([$id, (int)$amenityId]);
        }
    }

    response(200, ['message' => 'Lugar actualizado correctamente']);
}

function deletePlace(int $id): void {
    authRequired();

    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM places WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) response(404, ['error' => 'Lugar no encontrado']);

    // Soft delete: solo desactiva el lugar
    $db->prepare("UPDATE places SET is_active = 0 WHERE id = ?")->execute([$id]);

    response(200, ['message' => 'Lugar eliminado correctamente']);
}
