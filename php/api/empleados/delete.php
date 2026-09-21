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
$id   = (int)($data['id'] ?? 0);
if ($id <= 0) bad('id requerido.');

try {
  $pdo = db();

  // Siempre soft-delete: un empleado puede tener historial de nómina
  // asociado (nomina_pagos referencia empleado_id), y borrarlo físicamente
  // se llevaría ese historial de pagos entre las patas. Simplemente se
  // marca como inactivo y deja de aparecer en las listas activas.
  $st = $pdo->prepare("UPDATE empleados SET activo = 0 WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  echo json_encode(['ok' => true, 'mode' => 'soft'], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
