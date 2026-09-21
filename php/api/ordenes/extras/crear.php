<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 4) . '/includes/guard_api.php';
require_once dirname(__DIR__, 4) . '/config/db.php';
require_once dirname(__DIR__, 4) . '/includes/functions.php';

// Cualquier usuario logueado puede proponer un extra por mano de obra
// (incluye al mecánico desde la app móvil): escribe un monto sugerido +
// la razón. La cajera/admin revisa y aprueba/ajusta antes de que cuente
// en el total de la orden (ver extras/revisar.php).

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

  $orden_id = isset($data['orden_id']) ? (int)$data['orden_id'] : 0;
  $monto_sugerido = isset($data['monto_sugerido']) ? (float)$data['monto_sugerido'] : 0.0;
  $justificacion = trim((string)($data['justificacion'] ?? ''));

  if ($orden_id <= 0) respond(400, ["ok" => false, "error" => "orden_id requerido"]);
  if ($monto_sugerido <= 0) respond(400, ["ok" => false, "error" => "El monto sugerido debe ser mayor a 0"]);
  if ($justificacion === '') respond(400, ["ok" => false, "error" => "Escribe la razón del extra (ej. modelo de moto, trabajo adicional)"]);
  if (mb_strlen($justificacion) > 500) $justificacion = mb_substr($justificacion, 0, 500);

  $st = $pdo->prepare("SELECT id, estado FROM ordenes_trabajo WHERE id = ? LIMIT 1");
  $st->execute([$orden_id]);
  $orden = $st->fetch();
  if (!$orden) respond(404, ["ok" => false, "error" => "Orden no existe"]);
  if (in_array($orden['estado'], ['entregado', 'cancelado'], true)) {
    respond(400, ["ok" => false, "error" => "La orden ya está cerrada, no se pueden agregar extras"]);
  }

  $usuario_id = (int)($_SESSION['user']['id'] ?? 0) ?: null;

  $ins = $pdo->prepare("
    INSERT INTO orden_extras (orden_id, monto_sugerido, justificacion, estado, creado_por, creado_en)
    VALUES (:orden_id, :monto_sugerido, :justificacion, 'pendiente', :creado_por, NOW())
  ");
  $ins->execute([
    ":orden_id" => $orden_id,
    ":monto_sugerido" => $monto_sugerido,
    ":justificacion" => $justificacion,
    ":creado_por" => $usuario_id,
  ]);

  respond(201, [
    "ok" => true,
    "data" => [
      "id" => (int)$pdo->lastInsertId(),
      "orden_id" => $orden_id,
      "monto_sugerido" => number_format($monto_sugerido, 2, '.', ''),
      "justificacion" => $justificacion,
      "estado" => "pendiente",
    ],
  ]);
} catch (Throwable $e) {
  respond(500, ["ok" => false, "error" => "Error al agregar extra", "detail" => $e->getMessage()]);
}
