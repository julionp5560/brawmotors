<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../includes/guard_api.php";
require_once __DIR__ . "/../../../config/db.php";

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();

  $date = preg_replace('/[^0-9\-]/', '', (string)($_GET['date'] ?? date('Y-m-d')));
  if ($date === '') $date = date('Y-m-d');

  // Ventas pagadas del día (incluye cerradas o no cerradas)
  $st = $pdo->prepare("
    SELECT id, folio, fecha, cliente_nombre, metodo_pago, subtotal, iva, total, estado, corte_id
    FROM ventas
    WHERE DATE(COALESCE(fecha, created_at)) = ?
    ORDER BY id DESC
  ");
  $st->execute([$date]);
  $ventas = $st->fetchAll(PDO::FETCH_ASSOC);

  // Top servicios vendidos (solo ventas NO canceladas)
  $st2 = $pdo->prepare("
    SELECT d.descripcion,
           SUM(d.cantidad) AS qty,
           COALESCE(SUM(d.importe),0) AS total
    FROM venta_detalle d
    JOIN ventas v ON v.id = d.venta_id
    WHERE DATE(COALESCE(v.fecha, v.created_at)) = ?
      AND COALESCE(v.estado,'pagada') <> 'cancelada'
    GROUP BY d.descripcion
    ORDER BY total DESC
    LIMIT 15
  ");
  $st2->execute([$date]);
  $top = $st2->fetchAll(PDO::FETCH_ASSOC);

  // Totales del día (en vivo)
  $st3 = $pdo->prepare("
    SELECT
      COUNT(*) AS n,
      COALESCE(SUM(total),0) AS total
    FROM ventas
    WHERE DATE(COALESCE(fecha, created_at)) = ?
      AND COALESCE(estado,'pagada') <> 'cancelada'
  ");
  $st3->execute([$date]);
  $sum = $st3->fetch(PDO::FETCH_ASSOC) ?: ['n'=>0,'total'=>0];

  echo json_encode([
    'ok'=>true,
    'date'=>$date,
    'summary'=>['n'=>(int)$sum['n'], 'total'=>(float)$sum['total']],
    'ventas'=>$ventas,
    'top_servicios'=>$top
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
