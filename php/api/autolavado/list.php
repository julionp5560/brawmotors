<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/includes/citas_activacion.php';

try {
  $pdo    = db();

  // Convierte en orden real cualquier cita de autolavado cuyo día ya
  // llegó, antes de listar (ver includes/citas_activacion.php).
  activar_citas_del_dia($pdo, 'autolavado');
  $estado = $_GET['estado'] ?? '';
  $fecha  = $_GET['fecha']  ?? '';

  $where = ['1=1'];
  $params = [];

  if ($estado !== '') {
    $where[]  = 'ao.estado = ?';
    $params[] = $estado;
  }
  $todasFechas = (int)($_GET['todas_fechas'] ?? 0) === 1;
  if ($fecha !== '') {
    $where[]  = 'DATE(ao.fecha_creacion) = ?';
    $params[] = $fecha;
  } elseif (!$todasFechas) {
    // Por defecto: solo de hoy. Se puede pedir todas las fechas (p.ej. para
    // limpiar pedidos pendientes acumulados de días anteriores) con
    // ?todas_fechas=1.
    $where[]  = 'DATE(ao.fecha_creacion) = CURDATE()';
  }

  $sql = "
    SELECT
      ao.id, ao.estado, ao.precio, ao.notas, ao.pagado,
      ao.fecha_creacion, ao.fecha_inicio, ao.fecha_fin,
      CASE WHEN s.es_personalizado = 1 THEN CONCAT('Cargo: ', COALESCE(NULLIF(ao.notas,''), 'personalizado')) ELSE s.nombre END AS servicio,
      s.tiempo_estimado,
      c.nombre_completo AS cliente,
      c.telefono,
      v.marca, v.modelo, v.placas AS placa,
      (SELECT COUNT(*) FROM autolavado_extras ae WHERE ae.orden_id = ao.id AND ae.estado = 'pendiente') AS extras_pendientes
    FROM autolavado_ordenes ao
    JOIN servicios s  ON s.id  = ao.servicio_id
    LEFT JOIN clientes  c ON c.id  = ao.cliente_id
    LEFT JOIN vehiculos v ON v.id  = ao.vehiculo_id
    WHERE " . implode(' AND ', $where) . "
    ORDER BY
      FIELD(ao.estado,'pendiente','en_proceso','terminado','entregado'),
      ao.fecha_creacion DESC
  ";

  $st = $pdo->prepare($sql);
  $st->execute($params);

  $rows = $st->fetchAll(PDO::FETCH_ASSOC);

  // El rol Mecánico (app móvil) jamás debe ver montos: se usa solo para
  // recibir/trabajar pedidos, el cobro lo hace la caja en la PC principal.
  if (is_operario()) {
    foreach ($rows as &$row) {
      unset($row['precio']);
    }
    unset($row);
  }

  foreach ($rows as &$row) { $row['pagado'] = (int)$row['pagado']; $row['extras_pendientes'] = (int)$row['extras_pendientes']; }
  unset($row);

  echo json_encode(['ok' => true, 'data' => $rows], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
