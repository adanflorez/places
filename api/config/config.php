<?php
// Carga las variables del archivo .env
$env = parse_ini_file(__DIR__ . '/../../.env');

define('DB_HOST', $env['DB_HOST'] ?? 'localhost');
define('DB_NAME', $env['DB_NAME'] ?? '');
define('DB_USER', $env['DB_USER'] ?? '');
define('DB_PASS', $env['DB_PASS'] ?? '');
define('JWT_SECRET', $env['JWT_SECRET'] ?? '');
define('JWT_EXPIRY', 60 * 60 * 8); // 8 horas
