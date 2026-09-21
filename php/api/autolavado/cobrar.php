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
if (is_operario()) bad('No autorizado: este rol no puede cobrar.', 403);

$data   = json_decode(file_get_contents('php://input') ?: '{}', true);
$id     = (int)($data['id']          ?? 0);
$pago   = (string)($data['metodo_pago'] ?? 'efectivo');

if (!in_array($pago, ['efectivo','tarjeta','transferencia'], true)) {
  $pago = 'efectivo';
}
if ($id <= 0) bad('ID de orden requerido.');

$user    = $_SESSION['user'];
$usuario_id = (int)$user['id'];

try {
  $pdo = db();

  // Obtener la orden con su servicio
  $st = $pdo->prepare("
    SELECT ao.*, s.nombre AS servicio_nombre, s.precio AS servicio_precio
    FROM   autolavado_ordenes ao
    JOIN   servicios s ON s.id = ao.servicio_id
    WHERE  ao.id = ?
    LIMIT  1
  ");
  $st->execute([$id]);
  $orden = $st->fetch(PDO::FETCH_ASSOC);

  if (!$orden) bad('Orden no encontrada.');
  if ((int)($orden['pagado'] ?? 0) === 1) bad('Esta orden ya fue cobrada.');

  $pdo->beginTransaction();

  $precioServicio = (float)$orden['precio'];
  $nombre   = (string)$orden['servicio_nombre'];

  // Extras por mano de obra ya aprobados por caja/admin (los pendientes
  // o rechazados no se cobran). Ej: lavar una moto grande/sucia cuesta
  // más que el precio base del servicio.
  $stExtras = $pdo->prepare("
    SELECT id, monto_final, justificacion
    FROM autolavado_extras
    WHERE orden_id = ? AND estado = 'aprobado'
    ORDER BY id ASC
  ");
  $stExtras->execute([$id]);
  $extrasAprobados = $stExtras->fetchAll(PDO::FETCH_ASSOC);
  $sumExtras = 0.0;
  foreach ($extrasAprobados as $ex) { $sumExtras += (float)$ex['monto_final']; }

  $precio = $precioServicio + $sumExtras;

  // Generar folio
  $st2 = $pdo->query("SELECT COUNT(*) FROM ventas WHERE DATE(fecha) = CURDATE()");
  $num = str_pad((string)((int)$st2->fetchColumn() + 1), 4, '0', STR_PAD_LEFT);
  $folio = 'LAV-' . date('Ymd') . '-' . $num;

  // Insertar venta con TODOS los campos requeridos
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

  $cliente_id   = ($orden['cliente_id'] > 0) ? (int)$orden['cliente_id'] : null;
  $cliente_nom  = $cliente_id ? null : 'Mostrador'; // lo obtenemos de la orden si existe

  // Si hay cliente_id buscamos el nombre
  if ($cliente_id) {
    $stc = $pdo->prepare("SELECT nombre_completo FROM clientes WHERE id = ? LIMIT 1");
    $stc->execute([$cliente_id]);
    $cliente_nom = (string)($stc->fetchColumn() ?: 'Mostrador');
  } else {
    $cliente_nom = 'Mostrador';
  }

  $ins->execute([
    $folio,
    $cliente_nom,
    $cliente_id,
    $usuario_id,
    $precio,   // subtotal
    $precio,   // total
    $pago,
    $orden['notas'] ?? null,
  ]);

  $venta_id = (int)$pdo->lastInsertId();

  // Detalle de venta: el servicio en su propia línea...
  $det = $pdo->prepare("
    INSERT INTO venta_detalle
      (venta_id, tipo_item, item_id, descripcion, cantidad, precio_unitario, importe, subtotal)
    VALUES
      (?, 'servicio', ?, ?, 1, ?, ?, 0)
  ");
  $det->execute([$venta_id, (int)$orden['servicio_id'], $nombre, $precioServicio, $precioServicio]);

  // ...y cada extra aprobado (mano de obra) en su propia línea.
  if ($extrasAprobados) {
    $detExtra = $pdo->prepare("
      INSERT INTO venta_detalle
        (venta_id, tipo_item, item_id, descripcion, cantidad, precio_unitario, importe, subtotal)
      VALUES
        (?, 'servicio', ?, ?, 1, ?, ?, 0)
    ");
    foreach ($extrasAprobados as $ex) {
      $monto = (float)$ex['monto_final'];
      $desc = 'Extra: ' . mb_substr((string)$ex['justificacion'], 0, 180);
      $detExtra->execute([$venta_id, (int)$orden['servicio_id'], $desc, $monto, $monto]);
    }
  }

  // Marcar la orden como pagada. El trabajo se cobra ANTES de iniciar,
  // así que el estado (pendiente/en_proceso/terminado/entregado) no
  // cambia aquí: eso lo sigue llevando el lavador/caja por separado.
  $upd = $pdo->prepare("UPDATE autolavado_ordenes SET pagado = 1, venta_id = ?, precio = ? WHERE id = ?");
  $upd->execute([$venta_id, $precio, $id]);

  $pdo->commit();

  // Respaldo en PDF (no debe romper el cobro si falla; ver backup.php).
  guardar_venta_pdf($pdo, $venta_id);

  echo json_encode([
    'ok'       => true,
    'venta_id' => $venta_id,
    'folio'    => $folio,
    'total'    => number_format($precio, 2, '.', ''),
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
