<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../includes/guard_api.php";
require_once __DIR__ . "/../../../config/db.php";

header('Content-Type: application/json; charset=utf-8');

function bad(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$cliente_id = (int)($_GET['cliente_id'] ?? 0);
if ($cliente_id <= 0) bad('cliente_id requerido');

try {
  $pdo = db();

  $stCli = $pdo->prepare("SELECT id, nombre_completo, telefono, email, rfc, fecha_registro, notas FROM clientes WHERE id = ? LIMIT 1");
  $stCli->execute([$cliente_id]);
  $cliente = $stCli->fetch(PDO::FETCH_ASSOC);
  if (!$cliente) bad('Cliente no encontrado', 404);

  $stVeh = $pdo->prepare("SELECT id, marca, modelo, año, color, placas FROM vehiculos WHERE cliente_id = ? ORDER BY id DESC");
  $stVeh->execute([$cliente_id]);
  $vehiculos = $stVeh->fetchAll(PDO::FETCH_ASSOC);

  // Órdenes de taller de este cliente
  $stOrd = $pdo->prepare("
    SELECT ot.id, ot.folio, ot.estado, ot.total, ot.fecha_entrada, ot.fecha_entrega,
           v.marca AS vehiculo_marca, v.modelo AS vehiculo_modelo, v.placas AS vehiculo_placas
    FROM ordenes_trabajo ot
    LEFT JOIN vehiculos v ON v.id = ot.vehiculo_id
    WHERE ot.cliente_id = ?
    ORDER BY ot.fecha_entrada DESC
  ");
  $stOrd->execute([$cliente_id]);
  $ordenes = $stOrd->fetchAll(PDO::FETCH_ASSOC);

  // Ventas (mostrador / autolavado / servicio) asociadas a este cliente
  $stVta = $pdo->prepare("
    SELECT id, folio, fecha, tipo, total, metodo_pago, estado
    FROM ventas
    WHERE cliente_id = ?
    ORDER BY fecha DESC
  ");
  $stVta->execute([$cliente_id]);
  $ventas = $stVta->fetchAll(PDO::FETCH_ASSOC);

  // Resumen: solo se cuenta lo efectivamente cobrado (no cancelado)
  $totalGastado = 0.0;
  $visitas = 0;
  foreach ($ventas as $v) {
    if (($v['estado'] ?? '') !== 'cancelada' && ($v['estado'] ?? '') !== 'cancelado') {
      $totalGastado += (float)$v['total'];
      $visitas++;
    }
  }
  foreach ($ordenes as $o) {
    if (($o['estado'] ?? '') === 'entregado') {
      $totalGastado += (float)$o['total'];
      $visitas++;
    }
  }

  $ultimaVisita = null;
  foreach (array_merge(
    array_column($ventas, 'fecha'),
    array_column($ordenes, 'fecha_entrada')
  ) as $f) {
    if ($f && ($ultimaVisita === null || $f > $ultimaVisita)) $ultimaVisita = $f;
  }

  echo json_encode([
    'ok' => true,
    'data' => [
      'cliente' => $cliente,
      'vehiculos' => $vehiculos,
      'ordenes' => $ordenes,
      'ventas' => $ventas,
      'resumen' => [
        'total_gastado' => number_format($totalGastado, 2, '.', ''),
        'visitas' => $visitas,
        'ultima_visita' => $ultimaVisita,
      ],
    ],
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => 'Error al cargar historial', 'detail' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
