<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

// Cualquier rol logueado puede ver la agenda (Admin, Caja, Mecánico,
// Lavador) — no hay datos de dinero aquí, solo horarios.
try {
  $pdo = db();

  $fecha  = trim((string)($_GET['fecha']  ?? ''));   // día exacto (YYYY-MM-DD)
  $desde  = trim((string)($_GET['desde']  ?? ''));
  $hasta  = trim((string)($_GET['hasta']  ?? ''));
  $area   = trim((string)($_GET['area']   ?? ''));
  $area   = in_array($area, ['taller','autolavado','general'], true) ? $area : '';
  $incluirCanceladas = (int)($_GET['incluir_canceladas'] ?? 0) === 1;

  $where = [];
  $params = [];

  if ($fecha !== '') {
    $where[] = 'DATE(c.fecha_cita) = ?';
    $params[] = $fecha;
  } elseif ($desde !== '' && $hasta !== '') {
    $where[] = 'DATE(c.fecha_cita) BETWEEN ? AND ?';
    $params[] = $desde;
    $params[] = $hasta;
  }

  if ($area !== '') {
    $where[] = 'c.area = ?';
    $params[] = $area;
  }

  if (!$incluirCanceladas) {
    $where[] = "c.estado != 'cancelada'";
  }

  $sql = "SELECT c.id, c.cliente_id, c.vehiculo_id, c.servicio_id, c.titulo, c.area,
                 c.fecha_cita, c.duracion, c.duracion AS duracion_min, c.estado, c.notas, c.fecha_creacion,
                 cl.nombre_completo AS nombre_cliente, cl.telefono,
                 sv.nombre AS servicio_nombre
          FROM citas c
          LEFT JOIN clientes cl ON cl.id = c.cliente_id
          LEFT JOIN servicios sv ON sv.id = c.servicio_id";
  if ($where) $sql .= " WHERE " . implode(' AND ', $where);
  $sql .= " ORDER BY c.fecha_cita ASC";

  $st = $pdo->prepare($sql);
  $st->execute($params);
  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // Separar fecha_cita en fecha/hora para que el frontend no tenga que
  // parsear el datetime.
  foreach ($rows as &$r) {
    $r['fecha'] = substr($r['fecha_cita'], 0, 10);
    $r['hora']  = substr($r['fecha_cita'], 11, 5);
  }
  unset($r);

  echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
