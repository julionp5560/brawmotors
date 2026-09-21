<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../includes/guard_api_admin.php";
require_once __DIR__ . "/../../../config/db.php";

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();

  $producto_id = (int)($_GET['producto_id'] ?? 0);

  $sql = "
    SELECT m.id, m.tipo, m.cantidad, m.referencia, m.nota, m.created_at,
           p.nombre AS producto
    FROM inventario_movimientos m
    JOIN productos p ON p.id = m.producto_id
  ";
  $params = [];

  if ($producto_id > 0) {
    $sql .= " WHERE m.producto_id = ? ";
    $params[] = $producto_id;
  }

  $sql .= " ORDER BY m.id DESC LIMIT 200";

  $st = $pdo->prepare($sql);
  $st->execute($params);

  echo json_encode(['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
