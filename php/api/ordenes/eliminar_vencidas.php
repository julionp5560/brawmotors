<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
$pdo = db();

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function body(): array {
  $raw = file_get_contents('php://input');
  $d = json_decode($raw ?: '{}', true);
  return is_array($d) ? $d : [];
}

try {
  $d = body();

  // Días de antigüedad a partir de los cuales una orden sin cerrar se
  // considera "vencida". Mínimo 1 para evitar borrar órdenes de hoy mismo.
  $dias = isset($d['dias']) ? (int)$d['dias'] : 3;
  if ($dias < 1) $dias = 1;

  $confirm = !empty($d['confirm']);

  // Estados que cuentan como "abierta" (no cerrada). Todo lo que no sea
  // entregado/cancelado y tenga fecha_entrada vieja es candidato a vencida.
  $st = $pdo->prepare("
    SELECT ot.id, ot.folio, ot.estado, ot.fecha_entrada, ot.total,
           c.nombre_completo AS cliente_nombre
    FROM ordenes_trabajo ot
    INNER JOIN clientes c ON c.id = ot.cliente_id
    WHERE ot.estado NOT IN ('entregado','cancelado')
      AND ot.fecha_entrada < (NOW() - INTERVAL :dias DAY)
    ORDER BY ot.fecha_entrada ASC
  ");
  $st->bindValue(':dias', $dias, PDO::PARAM_INT);
  $st->execute();
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  if (!$confirm) {
    // Modo preview: solo informamos qué se borraría, sin tocar nada.
    respond(200, ["ok" => true, "preview" => true, "count" => count($rows), "data" => $rows]);
  }

  $ids = array_column($rows, 'id');
  $deleted = 0;

  if ($ids) {
    $pdo->beginTransaction();
    try {
      $placeholders = implode(',', array_fill(0, count($ids), '?'));

      $pdo->prepare("DELETE FROM orden_servicios WHERE orden_id IN ($placeholders)")->execute($ids);
      $pdo->prepare("DELETE FROM orden_productos WHERE orden_id IN ($placeholders)")->execute($ids);
      $del = $pdo->prepare("DELETE FROM ordenes_trabajo WHERE id IN ($placeholders)");
      $del->execute($ids);
      $deleted = $del->rowCount();

      $pdo->commit();
    } catch (Throwable $e) {
      $pdo->rollBack();
      throw $e;
    }
  }

  respond(200, ["ok" => true, "preview" => false, "count" => $deleted, "data" => $rows]);

} catch (Throwable $e) {
  respond(500, ["ok" => false, "error" => "Error al eliminar órdenes vencidas", "detail" => $e->getMessage()]);
}
