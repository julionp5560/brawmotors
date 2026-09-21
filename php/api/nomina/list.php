<?php
declare(strict_types=1);

require_once dirname(__DIR__, 3) . '/includes/guard_api_admin.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

header('Content-Type: application/json; charset=utf-8');

try {
  $pdo = db();
  $empleado_id = (int)($_GET['empleado_id'] ?? 0);

  if ($empleado_id > 0) {
    $st = $pdo->prepare("
      SELECT np.id, np.empleado_id, np.periodo_inicio, np.periodo_fin, np.monto,
             np.fecha_pago, np.metodo_pago, np.notas, np.fecha_registro,
             u.nombre_completo AS registrado_por
      FROM nomina_pagos np
      LEFT JOIN usuarios u ON u.id = np.usuario_id
      WHERE np.empleado_id = ?
      ORDER BY np.fecha_pago DESC, np.id DESC
    ");
    $st->execute([$empleado_id]);
  } else {
    $st = $pdo->query("
      SELECT np.id, np.empleado_id, e.nombre_completo AS empleado_nombre,
             np.periodo_inicio, np.periodo_fin, np.monto, np.fecha_pago,
             np.metodo_pago, np.notas, np.fecha_registro
      FROM nomina_pagos np
      INNER JOIN empleados e ON e.id = np.empleado_id
      ORDER BY np.fecha_pago DESC, np.id DESC
      LIMIT 200
    ");
  }

  echo json_encode(['ok' => true, 'data' => $st->fetchAll(PDO::FETCH_ASSOC)], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
