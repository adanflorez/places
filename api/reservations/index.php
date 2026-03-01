<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/helpers.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');
header('Access-Control-Allow-Headers: Authorization, Content-Type');
header('Access-Control-Allow-Methods: GET, POST, PUT, OPTIONS');

if ($_SERVER['REQUEST_METHOD'] === 'OPTIONS') exit;

$id     = isset($_GET['id']) ? (int)$_GET['id'] : null;
$method = $_SERVER['REQUEST_METHOD'];

if (!$id) {
    match ($method) {
        'GET'  => listReservations(),
        'POST' => createReservation(),
        default => response(405, ['error' => 'Método no permitido']),
    };
}

match ($method) {
    'GET' => showReservation($id),
    'PUT' => updateStatus($id),
    default => response(405, ['error' => 'Método no permitido']),
};

// ============================================================

function listReservations(): void {
    authRequired();

    $db     = getDB();
    $where  = 'WHERE 1=1';
    $params = [];

    // Filtros opcionales por query string
    if (!empty($_GET['place_id'])) {
        $where   .= ' AND r.place_id = ?';
        $params[] = (int)$_GET['place_id'];
    }
    if (!empty($_GET['status'])) {
        $where   .= ' AND r.status = ?';
        $params[] = $_GET['status'];
    }
    // Filtro por mes: ?month=2026-03
    if (!empty($_GET['month'])) {
        $where   .= ' AND DATE_FORMAT(r.check_in, "%Y-%m") = ?';
        $params[] = $_GET['month'];
    }

    $stmt = $db->prepare("
        SELECT r.id, r.guest_name, r.guest_email, r.guest_phone,
               r.check_in, r.check_out, r.guests, r.total_price,
               r.status, r.notes, r.created_at,
               p.name AS place_name
        FROM reservations r
        JOIN places p ON p.id = r.place_id
        $where
        ORDER BY r.check_in DESC
    ");
    $stmt->execute($params);
    response(200, $stmt->fetchAll());
}

function showReservation(int $id): void {
    authRequired();

    $db   = getDB();
    $stmt = $db->prepare("
        SELECT r.*, p.name AS place_name, p.location AS place_location
        FROM reservations r
        JOIN places p ON p.id = r.place_id
        WHERE r.id = ?
    ");
    $stmt->execute([$id]);
    $reservation = $stmt->fetch();

    if (!$reservation) response(404, ['error' => 'Reserva no encontrada']);
    response(200, $reservation);
}

function createReservation(): void {
    authRequired(); // solo el admin puede crear reservas
    $body     = json_decode(file_get_contents('php://input'), true);
    $required = ['place_id', 'guest_name', 'guest_email', 'guest_phone', 'check_in', 'check_out', 'guests'];

    foreach ($required as $field) {
        if (empty($body[$field])) response(400, ['error' => "El campo '$field' es requerido"]);
    }

    $checkIn  = $body['check_in'];
    $checkOut = $body['check_out'];

    if ($checkIn >= $checkOut) {
        response(400, ['error' => 'La fecha de salida debe ser posterior a la de entrada']);
    }
    if ($checkIn < date('Y-m-d')) {
        response(400, ['error' => 'La fecha de entrada no puede ser en el pasado']);
    }

    $db = getDB();

    // Verificar que el lugar existe y está activo
    $stmt = $db->prepare("SELECT id, price_per_night, max_guests FROM places WHERE id = ? AND is_active = 1");
    $stmt->execute([$body['place_id']]);
    $place = $stmt->fetch();
    if (!$place) response(404, ['error' => 'El lugar no existe o no está disponible']);

    // Validar cantidad de huéspedes
    if ((int)$body['guests'] > $place['max_guests']) {
        response(400, ['error' => "El lugar admite máximo {$place['max_guests']} huéspedes"]);
    }

    // Verificar disponibilidad (sin solapamientos)
    $stmt = $db->prepare("
        SELECT id FROM reservations
        WHERE place_id = ?
          AND status != 'cancelled'
          AND check_in  < ?
          AND check_out > ?
    ");
    $stmt->execute([$body['place_id'], $checkOut, $checkIn]);
    if ($stmt->fetch()) {
        response(409, ['error' => 'El lugar no está disponible en las fechas seleccionadas']);
    }

    // Calcular precio total
    $nights     = (new DateTime($checkIn))->diff(new DateTime($checkOut))->days;
    $totalPrice = $nights * $place['price_per_night'];

    $stmt = $db->prepare("
        INSERT INTO reservations (place_id, guest_name, guest_email, guest_phone, check_in, check_out, guests, total_price, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
    ");
    $stmt->execute([
        $body['place_id'],
        $body['guest_name'],
        $body['guest_email'],
        $body['guest_phone'],
        $checkIn,
        $checkOut,
        $body['guests'],
        $totalPrice,
        $body['notes'] ?? null,
    ]);

    response(201, [
        'id'          => (int)$db->lastInsertId(),
        'total_price' => $totalPrice,
        'nights'      => $nights,
        'message'     => 'Reserva creada correctamente. Pronto recibirás confirmación.',
    ]);
}

function updateStatus(int $id): void {
    authRequired();
    $body = json_decode(file_get_contents('php://input'), true);

    $allowed = ['pending', 'confirmed', 'cancelled'];
    if (empty($body['status']) || !in_array($body['status'], $allowed)) {
        response(400, ['error' => 'Estado inválido. Valores permitidos: pending, confirmed, cancelled']);
    }

    $db   = getDB();
    $stmt = $db->prepare("SELECT id FROM reservations WHERE id = ?");
    $stmt->execute([$id]);
    if (!$stmt->fetch()) response(404, ['error' => 'Reserva no encontrada']);

    $db->prepare("UPDATE reservations SET status = ? WHERE id = ?")->execute([$body['status'], $id]);

    response(200, ['message' => 'Estado actualizado correctamente']);
}
