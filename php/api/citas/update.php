<?php
declare(strict_types=1);
header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';

// Editar una cita existente (reagendar, cambiar datos). Cualquier rol
// logueado puede editar — es agenda compartida, no hay dueño exclusivo.
function bad(string $msg): void {
  http_response_code(400);
  echo json_encode(['ok' => false, 'error' => $msg], JSON_UNESCAPED_UNICODE);
  exit;
}

$data = json_decode(file_get_contents('php://input') ?: '{}', true);
if (!is_array($data)) bad('JSON inválido.');

$id = (int)($data['id'] ?? 0);
if ($id <= 0) bad('ID inválido.');

$nombre     = trim((string)($data['nombre_cliente'] ?? ''));
$telefono   = trim((string)($data['telefono']       ?? ''));
$area       = (string)($data['area'] ?? 'general');
$titulo     = trim((string)($data['titulo'] ?? ''));
$servicioId = (int)($data['servicio_id'] ?? 0) ?: null;
$fecha    = trim((string)($data['fecha']  ?? ''));
$hora     = trim((string)($data['hora']   ?? ''));
$duracion = (int)($data['duracion_min'] ?? $data['duracion'] ?? 60);
$notas    = trim((string)($data['notas']  ?? ''));

if (!$titulo) bad('Falta el motivo de la cita.');
if (!in_array($area, ['taller','autolavado','general'], true)) bad('Área inválida.');
if (!preg_match('/^\d{4}-\d{2}-\d{2}$/', $fecha)) bad('Fecha inválida.');
if (!preg_match('/^\d{2}:\d{2}(:\d{2})?$/', $hora)) bad('Hora inválida.');
if ($duracion <= 0) $duracion = 60;

try {
  $pdo = db();

  $st = $pdo->prepare("SELECT cliente_id FROM citas WHERE id=? LIMIT 1");
  $st->execute([$id]);
  $clienteId = $st->fetchColumn();
  if (!$clienteId) bad('Cita no encontrada.');

  if ($nombre) {
    $pdo->prepare("UPDATE clientes SET nombre_completo=?, telefono=? WHERE id=? LIMIT 1")
        ->execute([$nombre, $telefono ?: null, $clienteId]);
  }

  $fechaCita = "$fecha $hora" . (strlen($hora) === 5 ? ':00' : '');

  $upd = $pdo->prepare("UPDATE citas SET titulo=?, area=?, servicio_id=?, fecha_cita=?, duracion=?, notas=? WHERE id=? LIMIT 1");
  $upd->execute([$titulo, $area, $servicioId, $fechaCita, $duracion, $notas ?: null, $id]);

  echo json_encode(['ok' => true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  http_response_code(500);
  echo json_encode(['ok' => false, 'error' => $e->getMessage()], JSON_UNESCAPED_UNICODE);
}
