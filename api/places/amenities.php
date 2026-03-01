<?php
require_once __DIR__ . '/../config/database.php';

header('Content-Type: application/json');
header('Access-Control-Allow-Origin: *');

$stmt = getDB()->query("SELECT id, name, icon FROM amenities ORDER BY name");
echo json_encode($stmt->fetchAll());
