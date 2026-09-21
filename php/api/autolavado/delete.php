<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api_admin.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

// Borrado manual de una orden de autolavado — pensado para limpiar
// pedidos duplicados que a veces se crean por reintentos cuando la red
// local va lenta desde la app móvil. Solo administración puede hacerlo.
// Si la orden ya se había cobrado (pagado=1, venta_id asignado), la venta
// NO se toca — se queda en Ventas/Corte como corresponde, solo se borra
// el registro operativo de la orden.
function bad(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
$id = (int)($data['id'] ?? 0);
if ($id <= 0) bad('ID inválido.');

try {
  $pdo = db();

  $st = $pdo->prepare("SELECT id, pagado, venta_id FROM autolavado_ordenes WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $orden = $st->fetch(PDO::FETCH_ASSOC);
  if (!$orden) bad('Orden no encontrada.', 404);

  $pdo->beginTransaction();

  $existeExtras = $pdo->query("SELECT 1 FROM information_schema.TABLES WHERE TABLE_SCHEMA=DATABASE() AND TABLE_NAME='autolavado_extras'")->fetchColumn();
  if ($existeExtras) {
    $pdo->prepare("DELETE FROM autolavado_extras WHERE orden_id=?")->execute([$id]);
  }

  $pdo->prepare("DELETE FROM autolavado_ordenes WHERE id=? LIMIT 1")->execute([$id]);

  $pdo->commit();

  echo json_encode([
    'ok' => true,
    'tenia_venta' => !empty($orden['venta_id']),
  ], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if ($pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
