<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../includes/guard_api_admin.php";
require_once __DIR__ . "/../../../config/db.php";

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $st = $pdo->query("SELECT id, nombre, activo FROM categorias_producto WHERE activo=1 ORDER BY nombre ASC");
  echo json_encode(['ok'=>true,'data'=>$st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
