<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/includes/backup.php';

function bad(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

// El rol Mecánico (app móvil) no maneja dinero: no puede cobrar órdenes.
// Se cobra ANTES de iniciar el trabajo (ver ordenes/cambiar_estado.php,
// que bloquea pasar a en_proceso si la orden no está pagada).
if (is_operario()) bad('No autorizado: este rol no puede cobrar.', 403);

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
$id   = (int)($data['id'] ?? 0);
$pago = (string)($data['metodo_pago'] ?? 'efectivo');

if (!in_array($pago, ['efectivo', 'tarjeta', 'transferencia'], true)) $pago = 'efectivo';
if ($id <= 0) bad('ID de orden requerido.');

$user       = $_SESSION['user'];
$usuario_id = (int)$user['id'];

try {
  $pdo = db();

  $st = $pdo->prepare("SELECT * FROM ordenes_trabajo WHERE id = ? LIMIT 1");
  $st->execute([$id]);
  $orden = $st->fetch(PDO::FETCH_ASSOC);

  if (!$orden) bad('Orden no encontrada.');
  if ((int)$orden['pagado'] === 1) bad('Esta orden ya fue cobrada.');
  if (in_array($orden['estado'], ['cancelado'], true)) bad('La orden está cancelada.');

  $pdo->beginTransaction();

  // Total = servicios + productos + extras aprobados
  $sumServ = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_servicios WHERE orden_id = {$id}")->fetchColumn();
  $sumProd = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_productos WHERE orden_id = {$id}")->fetchColumn();
  $sumExtras = (float)$pdo->query("SELECT COALESCE(SUM(monto_final),0) FROM orden_extras WHERE orden_id = {$id} AND estado = 'aprobado'")->fetchColumn();
  $precio = $sumServ + $sumProd + $sumExtras;

  // Folio
  $st2 = $pdo->query("SELECT COUNT(*) FROM ventas WHERE DATE(fecha) = CURDATE()");
  $num = str_pad((string)((int)$st2->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
  $folio = 'TAL-' . date('Ymd') . '-' . $num;

  $cliente_id = ((int)($orden['cliente_id'] ?? 0)) > 0 ? (int)$orden['cliente_id'] : null;
  $cliente_nom = 'Mostrador';
  if ($cliente_id) {
    $stc = $pdo->prepare("SELECT nombre_completo FROM clientes WHERE id = ? LIMIT 1");
    $stc->execute([$cliente_id]);
    $cliente_nom = (string)($stc->fetchColumn() ?: 'Mostrador');
  }

  $ins = $pdo->prepare("
    INSERT INTO ventas
      (folio, fecha, cliente_nombre, cliente_id, usuario_id, tipo,
       subtotal, iva_rate, descuento, iva, total,
       metodo_pago, estado, notas)
    VALUES
      (?, NOW(), ?, ?, ?, 'servicio',
       ?, 0.00, 0.00, 0.00, ?,
       ?, 'pagada', ?)
  ");
  $ins->execute([
    $folio, $cliente_nom, $cliente_id, $usuario_id,
    $precio, $precio, $pago,
    'Orden de trabajo ' . (string)$orden['folio'],
  ]);

  $venta_id = (int)$pdo->lastInsertId();

  // Detalle: servicios de la orden
  $stS = $pdo->prepare("
    SELECT os.servicio_id, s.nombre, os.cantidad, os.precio_unitario, os.subtotal
    FROM orden_servicios os JOIN servicios s ON s.id = os.servicio_id
    WHERE os.orden_id = ?
  ");
  $stS->execute([$id]);
  $det = $pdo->prepare("
    INSERT INTO venta_detalle (venta_id, tipo_item, item_id, descripcion, cantidad, precio_unitario, importe, subtotal)
    VALUES (?, 'servicio', ?, ?, ?, ?, ?, 0)
  ");
  foreach ($stS->fetchAll(PDO::FETCH_ASSOC) as $s) {
    $det->execute([$venta_id, (int)$s['servicio_id'], (string)$s['nombre'], (float)$s['cantidad'], (float)$s['precio_unitario'], (float)$s['subtotal']]);
  }

  // Detalle: productos de la orden
  $stP = $pdo->prepare("
    SELECT op.producto_id, p.nombre, op.cantidad, op.precio_unitario, op.subtotal
    FROM orden_productos op JOIN productos p ON p.id = op.producto_id
    WHERE op.orden_id = ?
  ");
  $stP->execute([$id]);
  $detP = $pdo->prepare("
    INSERT INTO venta_detalle (venta_id, tipo_item, item_id, descripcion, cantidad, precio_unitario, importe, subtotal)
    VALUES (?, 'producto', ?, ?, ?, ?, ?, 0)
  ");
  foreach ($stP->fetchAll(PDO::FETCH_ASSOC) as $p) {
    $detP->execute([$venta_id, (int)$p['producto_id'], (string)$p['nombre'], (float)$p['cantidad'], (float)$p['precio_unitario'], (float)$p['subtotal']]);
  }

  // Detalle: extras aprobados
  $stE = $pdo->prepare("SELECT monto_final, justificacion FROM orden_extras WHERE orden_id = ? AND estado = 'aprobado'");
  $stE->execute([$id]);
  $detE = $pdo->prepare("
    INSERT INTO venta_detalle (venta_id, tipo_item, item_id, descripcion, cantidad, precio_unitario, importe, subtotal)
    VALUES (?, 'servicio', NULL, ?, 1, ?, ?, 0)
  ");
  foreach ($stE->fetchAll(PDO::FETCH_ASSOC) as $ex) {
    $monto = (float)$ex['monto_final'];
    $desc = 'Extra: ' . mb_substr((string)$ex['justificacion'], 0, 180);
    $detE->execute([$venta_id, $desc, $monto, $monto]);
  }

  // Marcar orden como pagada (no cambia el estado de trabajo)
  $upd = $pdo->prepare("UPDATE ordenes_trabajo SET pagado = 1, venta_id = ?, total = ? WHERE id = ?");
  $upd->execute([$venta_id, $precio, $id]);

  $pdo->commit();

  // Respaldo en PDF (no debe romper el cobro si falla; ver backup.php).
  guardar_venta_pdf($pdo, $venta_id);

  echo json_encode([
    'ok' => true,
    'venta_id' => $venta_id,
    'folio' => $folio,
    'total' => number_format($precio, 2, '.', ''),
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
