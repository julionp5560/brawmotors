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
$id     = (int)($data['id'] ?? 0);
$nombre = trim((string)($data['nombre_completo'] ?? ''));
$tel    = trim((string)($data['telefono'] ?? ''));

if ($id <= 0)  bad('ID requerido.');
if (!$nombre)  bad('Nombre requerido.');
if (!$tel)     bad('Teléfono requerido.');

try {
  $pdo = db();
  $st  = $pdo->prepare("
    UPDATE clientes
    SET nombre_completo=?, telefono=?, email=?, rfc=?, fecha_nacimiento=?, notas=?,
        fecha_actualizacion=NOW()
    WHERE id=?
  ");
  $st->execute([
    $nombre, $tel,
    $data['email']            ?? null,
    $data['rfc']              ?? null,
    $data['fecha_nacimiento'] ?? null,
    $data['notas']            ?? null,
    $id,
  ]);
  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
