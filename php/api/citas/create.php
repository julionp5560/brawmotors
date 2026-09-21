<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

// Cualquier rol logueado (Admin, Caja, Mecánico, Lavador) puede agendar
// una cita. La tabla citas exige cliente_id (FK a clientes), pero para que
// agendar siga siendo rápido no se obliga a pasar primero por la pantalla
// de Clientes: si no se manda cliente_id, se busca un cliente existente
// por teléfono o se crea uno nuevo al vuelo con nombre_cliente/telefono.
function bad(string $msg): void {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($data)) bad('JSON inválido.');

$clienteId  = (int)($data['cliente_id'] ?? 0) ?: null;
$nombre     = trim((string)($data['nombre_cliente'] ?? ''));
$telefono   = trim((string)($data['telefono']       ?? ''));
$area       = (string)($data['area'] ?? 'general');
$titulo     = trim((string)($data['titulo'] ?? ''));
$servicioId = (int)($data['servicio_id'] ?? 0) ?: null;
$fecha     = trim((string)($data['fecha']  ?? ''));
$hora      = trim((string)($data['hora']   ?? ''));
$duracion  = (int)($data['duracion_min'] ?? $data['duracion'] ?? 60);
$notas     = trim((string)($data['notas']  ?? ''));

if (!$clienteId && !$nombre) bad('Falta el nombre del cliente.');
if (!$titulo) bad('Falta el motivo de la cita.');
if (!in_array($area, ['taller','autolavado','general'], true)) bad('Área inválida.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) bad('Fecha inválida.');
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) bad('Hora inválida.');
if ($duracion <= 0) $duracion = 60;

try {
  $pdo = db();

  if (!$clienteId) {
    // Reutilizar cliente existente por teléfono si coincide, para no
    // duplicar el mismo cliente cada vez que agenda.
    if ($telefono) {
      $st = $pdo->prepare("SELECT id FROM clientes WHERE telefono = ? LIMIT 1");
      $st->execute([$telefono]);
      $clienteId = (int)($st->fetchColumn() ?: 0) ?: null;
    }
    if (!$clienteId) {
      $st = $pdo->prepare("INSERT INTO clientes (nombre_completo, telefono, fecha_registro) VALUES (?, ?, NOW())");
      $st->execute([$nombre ?: 'Cliente sin nombre', $telefono ?: null]);
      $clienteId = (int)$pdo->lastInsertId();
    }
  }

  $fechaCita = "$fecha $hora" . (strlen($hora) === 5 ? ':00' : '');

  $st = $pdo->prepare("
    INSERT INTO citas (cliente_id, servicio_id, titulo, area, fecha_cita, duracion, estado, notas, creado_por, fecha_creacion)
    VALUES (?, ?, ?, ?, ?, ?, 'pendiente', ?, ?, NOW())
  ");
  $st->execute([
    $clienteId, $servicioId, $titulo, $area, $fechaCita, $duracion, $notas ?: null,
    $_SESSION['user']['id'] ?? null,
  ]);

  echo json_encode(['ok' => true, 'id' => (int)$pdo->lastInsertId(), 'cliente_id' => $clienteId], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
