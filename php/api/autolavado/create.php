<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

function bad(string $msg, int $code = 400): void {
  http_response_code($code);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data        = json_decode(file_get_contents('php://input') ?: '{}', true);
$servicio_id = (int)($data['servicio_id'] ?? 0);
$cliente_id  = (int)($data['cliente_id']  ?? 0) ?: null;
$vehiculo_id = (int)($data['vehiculo_id'] ?? 0) ?: null;
$notas       = trim((string)($data['notas'] ?? ''));

if ($servicio_id <= 0) bad('Servicio requerido.');

try {
  $pdo = db();

  $st = $pdo->prepare("SELECT id, precio, precio_min, precio_max, es_personalizado FROM servicios WHERE id=? AND tipo='autolavado' AND activo=1 LIMIT 1");
  $st->execute([$servicio_id]);
  $srv = $st->fetch(PDO::FETCH_ASSOC);

  if (!$srv) bad('Servicio no válido o inactivo.');

  // Cargo personalizado: descripción libre (notas) + monto libre, pero
  // siempre acotado a $50–$2000 sin importar el rol — es un cargo aparte,
  // no pasa por aprobación, así que el límite se valida aquí siempre.
  if ((int)$srv['es_personalizado'] === 1) {
    if (is_operario()) bad('No autorizado.', 403);
    if ($notas === '') bad('Describe el cargo.');
    $p = (float)($data['precio'] ?? -1);
    if ($p < 50 || $p > 2000) bad('El monto debe estar entre $50 y $2000.');
    $precioFinal = $p;
  } else {
    // Algunos servicios (precios "desde", a cotizar) se cobran con un monto
    // ajustado en el POS en vez del precio guardado. Solo roles que sí
    // manejan dinero pueden mandar cualquier ajuste libre — un operario
    // (Lavador) nunca debe poder fijar un precio arbitrario desde la app
    // móvil. La única excepción: si el servicio tiene un rango sugerido
    // (precio_min/precio_max), el lavador SÍ puede elegir un monto, pero
    // solo si cae dentro de ese rango — así puede indicar cuánto cobrar
    // según el vehículo sin manejar dinero libremente.
    $precioFinal = (float)$srv['precio'];
    $tieneRango = $srv['precio_min'] !== null && $srv['precio_max'] !== null;

    if (!is_operario() && array_key_exists('precio', $data)) {
      $p = (float)$data['precio'];
      if ($p >= 0) $precioFinal = $p;
    } elseif (is_operario() && $tieneRango && array_key_exists('precio', $data)) {
      $p = (float)$data['precio'];
      if ($p >= (float)$srv['precio_min'] && $p <= (float)$srv['precio_max']) {
        $precioFinal = $p;
      }
    }
  }

  $ins = $pdo->prepare("
    INSERT INTO autolavado_ordenes (cliente_id, vehiculo_id, servicio_id, precio, notas, estado, fecha_creacion)
    VALUES (?, ?, ?, ?, ?, 'pendiente', NOW())
  ");
  $ins->execute([$cliente_id, $vehiculo_id, $servicio_id, $precioFinal, $notas ?: null]);

  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId()], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
