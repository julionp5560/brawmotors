<?php
declare(strict_types=1);

// Copia este archivo como config/db.php y pon aquí tus propios datos.
// config/db.php NUNCA debe subirse a git (ya está en .gitignore).

$esLocalhost = in_array($_SERVER['SERVER_ADDR'] ?? '', ['127.0.0.1', '::1'], true)
    || in_array($_SERVER['REMOTE_ADDR'] ?? '', ['127.0.0.1', '::1'], true);

error_reporting(E_ALL);
ini_set('display_errors', $esLocalhost ? '1' : '0');
ini_set('log_errors', '1');

$DB_HOST = "127.0.0.1";
$DB_NAME = "raw_motos";
$DB_USER = "root";
$DB_PASS = "";
$DB_CHARSET = "utf8mb4";

// Datos del negocio y config de impresión de tickets.
$NEGOCIO_NOMBRE = "MI NEGOCIO";
$NEGOCIO_SUCURSAL = "Punto de venta";
$NEGOCIO_DIRECCION = "Tu ciudad";
$NEGOCIO_TELEFONO = "---";
$TICKET_ANCHO_MM = 48;

$RESPALDO_CARPETA = __DIR__ . "/../respaldos";

$dsn = "mysql:host={$DB_HOST};dbname={$DB_NAME};charset={$DB_CHARSET}";
$options = [
  PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
  PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
  PDO::ATTR_EMULATE_PREPARES => false,
];

$conn = new PDO($dsn, $DB_USER, $DB_PASS, $options);

function db(): PDO {
  global $conn;
  return $conn;
}

// Migraciones automáticas e idempotentes: crean todas las tablas que hagan
// falta la primera vez que corre la app contra una base de datos vacía.
require_once __DIR__ . '/../includes/migrate.php';
try {
  run_migrations($conn);
} catch (Throwable $e) {
  error_log('run_migrations: ' . $e->getMessage());
}
