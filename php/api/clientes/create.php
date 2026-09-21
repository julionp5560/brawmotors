<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

function bad(string $msg): void {
  http_response_code(400);
  echo json_encode(['ok'=>false,'error'=>$msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data   = json_decode(file_get_contents('php://input') ?: '{}', true);
$nombre = trim((string)($data['nombre_completo'] ?? ''));
$tel    = trim((string)($data['telefono']        ?? ''));

if (!$nombre) bad('Nombre requerido.');
if (!$tel)    bad('Teléfono requerido.');

try {
  $pdo = db();
  $st  = $pdo->prepare("INSERT INTO clientes (nombre_completo, telefono) VALUES (?,?)");
  $st->execute([$nombre, $tel]);
  $id = (int)$pdo->lastInsertId();
  echo json_encode(['ok'=>true,'id'=>$id,'nombre_completo'=>$nombre,'telefono'=>$tel], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
