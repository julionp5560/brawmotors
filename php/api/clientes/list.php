<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

try {
  $pdo = db();
  $q   = '%' . trim((string)($_GET['q'] ?? '')) . '%';

  $st = $pdo->prepare("
    SELECT id, nombre_completo, telefono, email, rfc, fecha_nacimiento, notas, fecha_registro
    FROM clientes
    WHERE activo = 1
      AND (nombre_completo LIKE ? OR telefono LIKE ? OR email LIKE ?)
    ORDER BY nombre_completo
    LIMIT 50
  ");
  $st->execute([$q, $q, $q]);

  echo json_encode(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
