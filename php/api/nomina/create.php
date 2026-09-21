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

$empleado_id    = (int)($data['empleado_id'] ?? 0);
$periodoInicio  = trim((string)($data['periodo_inicio'] ?? ''));
$periodoFin     = trim((string)($data['periodo_fin'] ?? ''));
$monto          = (float)($data['monto'] ?? 0);
$fechaPago      = trim((string)($data['fecha_pago'] ?? ''));
$metodoPago     = trim((string)($data['metodo_pago'] ?? 'efectivo'));
$notas          = trim((string)($data['notas'] ?? ''));

if ($empleado_id <= 0) bad('Selecciona un empleado.');
if ($periodoInicio === '' || $periodoFin === '') bad('El periodo (inicio y fin) es requerido.');
if ($monto <= 0) bad('El monto debe ser mayor a cero.');
if ($fechaPago === '') $fechaPago = date('Y-m-d');
if (!in_array($metodoPago, ['efectivo', 'tarjeta', 'transferencia'], true)) $metodoPago = 'efectivo';

$usuario_id = (int)($_SESSION['user']['id'] ?? 0) ?: null;

try {
  $pdo = db();

  $chk = $pdo->prepare("SELECT id FROM empleados WHERE id = ? LIMIT 1");
  $chk->execute([$empleado_id]);
  if (!$chk->fetch()) bad('Empleado no encontrado.', 404);

  $st = $pdo->prepare("
    INSERT INTO nomina_pagos (empleado_id, periodo_inicio, periodo_fin, monto, fecha_pago, metodo_pago, notas, usuario_id)
    VALUES (?, ?, ?, ?, ?, ?, ?, ?)
  ");
  $st->execute([$empleado_id, $periodoInicio, $periodoFin, $monto, $fechaPago, $metodoPago, $notas ?: null, $usuario_id]);

  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Error al registrar el pago', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
