<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 4) . '/includes/guard_api.php';
require_once dirname(__DIR__, 4) . '/config/db.php';
require_once dirname(__DIR__, 4) . '/includes/functions.php';

// Aprobar/rechazar un extra propuesto por el operario. Exclusivo de
// caja/admin: el rol Mecánico nunca decide montos finales.
if (is_operario()) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'No autorizado: solo caja/admin puede revisar extras.'], JSON_UNESCAPED_UNICODE);
  exit;
}

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

try {
  $pdo = db();
  $data = body();

  $id = isset($data['id']) ? (int)$data['id'] : 0;
  $accion = (string)($data['accion'] ?? '');
  if ($id <= 0) respond(400, ["ok" => false, "error" => "id requerido"]);
  if (!in_array($accion, ['aprobar', 'rechazar'], true)) respond(400, ["ok" => false, "error" => "accion debe ser 'aprobar' o 'rechazar'"]);

  $pdo->beginTransaction();

  $st = $pdo->prepare("SELECT id, orden_id, monto_sugerido FROM orden_extras WHERE id = ? LIMIT 1 FOR UPDATE");
  $st->execute([$id]);
  $extra = $st->fetch();
  if (!$extra) {
    $pdo->rollBack();
    respond(404, ["ok" => false, "error" => "Extra no encontrado"]);
  }

  $orden_id = (int)$extra['orden_id'];
  $usuario_id = (int)($_SESSION['user']['id'] ?? 0) ?: null;

  if ($accion === 'aprobar') {
    $monto_final = isset($data['monto_final']) && $data['monto_final'] !== ''
      ? (float)$data['monto_final']
      : (float)$extra['monto_sugerido'];
    if ($monto_final < 0) {
      $pdo->rollBack();
      respond(400, ["ok" => false, "error" => "El monto no puede ser negativo"]);
    }
    $upd = $pdo->prepare("
      UPDATE orden_extras
      SET estado = 'aprobado', monto_final = :monto_final, revisado_por = :revisado_por, revisado_en = NOW()
      WHERE id = :id
    ");
    $upd->execute([":monto_final" => $monto_final, ":revisado_por" => $usuario_id, ":id" => $id]);
  } else {
    $upd = $pdo->prepare("
      UPDATE orden_extras
      SET estado = 'rechazado', monto_final = 0, revisado_por = :revisado_por, revisado_en = NOW()
      WHERE id = :id
    ");
    $upd->execute([":revisado_por" => $usuario_id, ":id" => $id]);
  }

  // Recalcular total de la orden = servicios + productos + extras aprobados
  $sumServ = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_servicios WHERE orden_id = {$orden_id}")->fetchColumn();
  $sumProd = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_productos WHERE orden_id = {$orden_id}")->fetchColumn();
  $sumExtras = (float)$pdo->query("SELECT COALESCE(SUM(monto_final),0) FROM orden_extras WHERE orden_id = {$orden_id} AND estado = 'aprobado'")->fetchColumn();
  $total = $sumServ + $sumProd + $sumExtras;

  $pdo->prepare("UPDATE ordenes_trabajo SET total = :total WHERE id = :id")
      ->execute([":total" => $total, ":id" => $orden_id]);

  $pdo->commit();

  respond(200, ["ok" => true, "data" => ["orden_id" => $orden_id, "total_orden" => number_format($total, 2, '.', '')]]);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  respond(500, ["ok" => false, "error" => "Error al revisar extra", "detail" => $e->getMessage()]);
}
