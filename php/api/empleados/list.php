<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/guard_api_admin.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $st = $pdo->query("
    SELECT id, nombre_completo, puesto, telefono, email, sueldo_base,
           fecha_contratacion, activo, notas, fecha_registro
    FROM empleados
    ORDER BY activo DESC, nombre_completo ASC
  ");
  echo json_encode(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
