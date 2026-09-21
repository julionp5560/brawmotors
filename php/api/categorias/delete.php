<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../includes/guard_api_admin.php";
require_once __DIR__ . "/../../../config/db.php";

header('Content-Type: application/json; charset=utf-8');

function bad(string $m, int $c=400): void {
  http_response_code($c);
  echo json_encode(['ok'=>false,'error'=>$m], JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();
  $d = json_decode(file_get_contents("php://input") ?: "{}", true);
  if (!is_array($d)) bad("JSON inválido.");

  $id = (int)($d['id'] ?? 0);
  if ($id <= 0) bad("ID inválido.");

  // Si hay productos usando esa categoría, la soltamos (SET NULL) primero
  $pdo->prepare("UPDATE productos SET categoria_id=NULL WHERE categoria_id=?")->execute([$id]);
  $pdo->prepare("UPDATE categorias_producto SET activo=0 WHERE id=? LIMIT 1")->execute([$id]);

  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
