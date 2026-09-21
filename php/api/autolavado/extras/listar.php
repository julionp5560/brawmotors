<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 4) . '/includes/guard_api.php';
require_once dirname(__DIR__, 4) . '/config/db.php';
require_once dirname(__DIR__, 4) . '/includes/functions.php';

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();
  $orden_id = isset($_GET['orden_id']) ? (int)$_GET['orden_id'] : 0;
  if ($orden_id <= 0) respond(400, ["ok" => false, "error" => "orden_id requerido"]);

  $st = $pdo->prepare("
    SELECT
      ae.id, ae.orden_id, ae.monto_sugerido, ae.monto_final, ae.justificacion,
      ae.estado, ae.creado_en, ae.revisado_en,
      u.nombre_completo AS creado_por_nombre
    FROM autolavado_extras ae
    LEFT JOIN usuarios u ON u.id = ae.creado_por
    WHERE ae.orden_id = ?
    ORDER BY ae.id ASC
  ");
  $st->execute([$orden_id]);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Rol Lavador (app móvil): ve lo que él mismo propuso, no el monto
  // final que decide la cajera.
  if (is_operario()) {
    foreach ($rows as &$r) {
      unset($r['monto_final'], $r['revisado_en']);
    }
    unset($r);
  }

  respond(200, ["ok" => true, "data" => $rows]);
} catch (Throwable $e) {
  respond(500, ["ok" => false, "error" => "Error al listar extras", "detail" => $e->getMessage()]);
}
