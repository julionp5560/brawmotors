<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/guard_api_admin.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

function bad(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($data)) bad('JSON inválido.');

$nombre     = trim((string)($data['nombre_completo'] ?? ''));
$puesto     = trim((string)($data['puesto'] ?? ''));
$telefono   = trim((string)($data['telefono'] ?? ''));
$email      = trim((string)($data['email'] ?? ''));
$sueldoBase = (float)($data['sueldo_base'] ?? 0);
$fechaContratacion = trim((string)($data['fecha_contratacion'] ?? ''));
$notas      = trim((string)($data['notas'] ?? ''));
$activo     = isset($data['activo']) ? (int)$data['activo'] : 1;

if ($nombre === '') bad('El nombre completo es requerido.');
if ($sueldoBase < 0) bad('El sueldo base no puede ser negativo.');

try {
  $pdo = db();
  $st = $pdo->prepare("
    INSERT INTO empleados (nombre_completo, puesto, telefono, email, sueldo_base, fecha_contratacion, activo, notas)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $st->execute([
    $nombre,
    $puesto ?: null,
    $telefono ?: null,
    $email ?: null,
    $sueldoBase,
    $fechaContratacion ?: null,
    $activo,
    $notas ?: null,
  ]);
  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Error al crear empleado', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
