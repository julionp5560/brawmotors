<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

try {
  $pdo = db();

  $id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
  if ($id <= 0) respond(400, ["ok" => false, "error" => "id requerido (ej: ?id=1)"]);

  // 1) Encabezado (orden + cliente + vehículo + usuario)
  $st = $pdo->prepare("
    SELECT
      ot.*,
      c.nombre_completo AS cliente_nombre,
      c.telefono AS cliente_telefono,
      c.email AS cliente_email,
      c.direccion AS cliente_direccion,

      v.marca AS vehiculo_marca,
      v.modelo AS vehiculo_modelo,
      v.año AS vehiculo_anio,
      v.color AS vehiculo_color,
      v.placas AS vehiculo_placas,
      v.numero_serie AS vehiculo_numero_serie,
      v.kilometraje AS vehiculo_kilometraje,

      u.nombre_completo AS usuario_nombre,
      u.usuario AS usuario_user
    FROM ordenes_trabajo ot
    INNER JOIN clientes c ON c.id = ot.cliente_id
    LEFT JOIN vehiculos v ON v.id = ot.vehiculo_id
    LEFT JOIN usuarios u ON u.id = ot.usuario_id
    WHERE ot.id = ?
    LIMIT 1
  ");
  $st->execute([$id]);
  $orden = $st->fetch();

  if (!$orden) respond(404, ["ok" => false, "error" => "Orden no encontrada"]);

  // 2) Servicios de la orden
  $st = $pdo->prepare("
    SELECT
      os.id,
      os.servicio_id,
      s.nombre AS servicio_nombre,
      s.tipo AS servicio_tipo,
      os.cantidad,
      os.precio_unitario,
      os.subtotal
    FROM orden_servicios os
    INNER JOIN servicios s ON s.id = os.servicio_id
    WHERE os.orden_id = ?
    ORDER BY os.id ASC
  ");
  $st->execute([$id]);
  $servicios = $st->fetchAll();

  // 3) Productos de la orden
  $st = $pdo->prepare("
    SELECT
      op.id,
      op.producto_id,
      p.codigo AS producto_codigo,
      p.nombre AS producto_nombre,
      op.cantidad,
      op.precio_unitario,
      op.subtotal
    FROM orden_productos op
    INNER JOIN productos p ON p.id = op.producto_id
    WHERE op.orden_id = ?
    ORDER BY op.id ASC
  ");
  $st->execute([$id]);
  $productos = $st->fetchAll();

  // 3b) Extras (recargo por dificultad) de la orden
  $st = $pdo->prepare("
    SELECT id, monto_sugerido, monto_final, justificacion, estado, creado_en, revisado_en
    FROM orden_extras
    WHERE orden_id = ?
    ORDER BY id ASC
  ");
  $st->execute([$id]);
  $extras = $st->fetchAll();

  // 4) Totales calculados (por si quieres validar vs ot.total)
  $st = $pdo->prepare("SELECT COALESCE(SUM(subtotal),0) AS total_servicios FROM orden_servicios WHERE orden_id = ?");
  $st->execute([$id]);
  $total_servicios = (float)$st->fetchColumn();

  $st = $pdo->prepare("SELECT COALESCE(SUM(subtotal),0) AS total_productos FROM orden_productos WHERE orden_id = ?");
  $st->execute([$id]);
  $total_productos = (float)$st->fetchColumn();

  $total_calculado = $total_servicios + $total_productos;

  // 5) (Opcional) Movimientos de inventario ligados a la orden
  // Si por alguna razón tu tabla no tuviera referencia_tipo/id, puedes comentar este bloque.
  $movimientos = [];
  try {
    $st = $pdo->prepare("
      SELECT
        mi.id,
        mi.producto_id,
        p.nombre AS producto_nombre,
        mi.tipo,
        mi.cantidad,
        mi.fecha,
        mi.nota,
        mi.usuario_id
      FROM movimientos_inventario mi
      LEFT JOIN productos p ON p.id = mi.producto_id
      WHERE mi.referencia_tipo = 'orden_trabajo' AND mi.referencia_id = ?
      ORDER BY mi.id ASC
    ");
    $st->execute([$id]);
    $movimientos = $st->fetchAll();
  } catch (Throwable $e) {
    $movimientos = [];
  }

  // Rol Mecánico (app móvil): nunca exponer precios/montos, ni de la
  // orden, ni de sus servicios/productos, ni los totales calculados.
  if (is_operario()) {
    unset($orden['total']);
    foreach ($servicios as &$s) { unset($s['precio_unitario'], $s['subtotal']); }
    unset($s);
    foreach ($productos as &$p) { unset($p['precio_unitario'], $p['subtotal']); }
    unset($p);
    foreach ($extras as &$ex) { unset($ex['monto_final'], $ex['revisado_en']); }
    unset($ex);
  }

  $payload = [
    "orden" => $orden,
    "servicios" => $servicios,
    "productos" => $productos,
    "extras" => $extras,
    "movimientos_inventario" => $movimientos,
  ];

  if (!is_operario()) {
    $payload["totales"] = [
      "total_servicios" => number_format($total_servicios, 2, '.', ''),
      "total_productos" => number_format($total_productos, 2, '.', ''),
      "total_calculado" => number_format($total_calculado, 2, '.', ''),
      "total_guardado_en_orden" => number_format((float)$orden["total"], 2, '.', '')
    ];
  }

  respond(200, ["ok" => true, "data" => $payload]);

} catch (Throwable $e) {
  respond(500, ["ok" => false, "error" => "Error al cargar detalle", "detail" => $e->getMessage()]);
}
