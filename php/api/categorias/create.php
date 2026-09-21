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

  $nombre = trim((string)($d['nombre'] ?? ''));
  if ($nombre === '') bad("Falta nombre.");

  $st = $pdo->prepare("INSERT INTO categorias_producto (nombre, activo) VALUES (?,1)");
  $st->execute([$nombre]);

  echo json_encode(['ok'=>true,'id'=>(int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if (str_contains($e->getMessage(), 'uq_cat_nombre')) {
    http_response_code(409);
    echo json_encode(['ok'=>false,'error'=>'Esa categoría ya existe.'], JSON_UNESCAPED_UNICODE);
    exit;
  }
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
