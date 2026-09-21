<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

// Marcar una cita como completada o cancelada (soft, nunca se borra el
// registro — mismo criterio que el resto de la app).
function bad(string $msg): void {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data   = json_decode(file_get_contents('php://input') ?: '{}', true);
$id     = (int)($data['id'] ?? 0);
$estado = (string)($data['estado'] ?? '');

if ($id <= 0) bad('ID inválido.');
if (!in_array($estado, ['pendiente','confirmada','en_proceso','completada','cancelada'], true)) bad('Estado inválido.');

try {
  $pdo = db();
  $st = $pdo->prepare("UPDATE citas SET estado=? WHERE id=? LIMIT 1");
  $st->execute([$estado, $id]);

  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
