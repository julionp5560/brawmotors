<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');

require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
$pdo = db();


function respond(int $code, array $payload): void {
  http_response_code($code);
  echo json_encode($payload, JSON_UNESCAPED_UNICODE);
  exit;
}

function getJsonBody(): array {
  $raw = file_get_contents('php://input');
  if (!$raw) return [];
  $data = json_decode($raw, true);
  return is_array($data) ? $data : [];
}

try {
  if (!isset($pdo) || !($pdo instanceof PDO)) {
    respond(500, ["ok" => false, "error" => "No hay conexión PDO (\$pdo). Revisa database.php"]);
  }

  $data = getJsonBody();

  // Campos mínimos
  $cliente_id = isset($data['cliente_id']) ? (int)$data['cliente_id'] : 0;
  $tipo = isset($data['tipo']) ? trim((string)$data['tipo']) : 'taller';
  $descripcion = isset($data['descripcion_problema']) ? trim((string)$data['descripcion_problema']) : '';
  $vehiculo_id = array_key_exists('vehiculo_id', $data) ? (int)$data['vehiculo_id'] : null;

  // usuario_id: si tienes sesión, úsala aquí. Por ahora aceptamos JSON o default 1.
  $usuario_id = isset($data['usuario_id']) ? (int)$data['usuario_id'] : 1;

  $fecha_entrada = isset($data['fecha_entrada']) ? trim((string)$data['fecha_entrada']) : null;
  $kilometraje = array_key_exists('kilometraje_entrada', $data) ? (int)$data['kilometraje_entrada'] : null;
  $notas = isset($data['notas']) ? trim((string)$data['notas']) : null;

  if ($cliente_id <= 0) {
    respond(400, ["ok" => false, "error" => "cliente_id es obligatorio"]);
  }
  if ($descripcion === '') {
    respond(400, ["ok" => false, "error" => "descripcion_problema es obligatorio"]);
  }
  if ($tipo !== 'taller' && $tipo !== 'autolavado') {
    respond(400, ["ok" => false, "error" => "tipo inválido (usa 'taller' o 'autolavado')"]);
  }

  // Si es autolavado permitimos vehiculo_id NULL
  if ($tipo === 'autolavado') {
    $vehiculo_id = null;
  } else {
    // taller: si quieres forzarlo, descomenta:
    // if (!$vehiculo_id || $vehiculo_id <= 0) respond(400, ["ok"=>false,"error"=>"vehiculo_id requerido para taller"]);
    if ($vehiculo_id !== null && $vehiculo_id <= 0) $vehiculo_id = null;
  }

  // fecha_entrada: si no mandan, usamos NOW()
  $fechaEntradaSql = $fecha_entrada ?: date('Y-m-d H:i:s');

  $pdo->beginTransaction();

  // Folio temporal (para poder insertar y luego armar folio final con el ID)
  $folioTmp = 'TMP-' . date('YmdHis') . '-' . random_int(1000, 9999);

  $stmt = $pdo->prepare("
    INSERT INTO ordenes_trabajo
      (folio, cliente_id, vehiculo_id, usuario_id, descripcion_problema, diagnostico, fecha_entrada,
       fecha_estimada_entrega, fecha_entrega, kilometraje_entrada, estado, total, notas)
    VALUES
      (:folio, :cliente_id, :vehiculo_id, :usuario_id, :descripcion, NULL, :fecha_entrada,
       NULL, NULL, :kilometraje, 'recibido', 0.00, :notas)
  ");

  $stmt->execute([
    ":folio" => $folioTmp,
    ":cliente_id" => $cliente_id,
    ":vehiculo_id" => $vehiculo_id,
    ":usuario_id" => $usuario_id,
    ":descripcion" => $descripcion,
    ":fecha_entrada" => $fechaEntradaSql,
    ":kilometraje" => $kilometraje,
    ":notas" => $notas,
  ]);

  $ordenId = (int)$pdo->lastInsertId();

  // Folio final: OT-YYYYMMDD-000123
  $folioFinal = 'OT-' . date('Ymd') . '-' . str_pad((string)$ordenId, 6, '0', STR_PAD_LEFT);

  $upd = $pdo->prepare("UPDATE ordenes_trabajo SET folio = :folio WHERE id = :id");
  $upd->execute([":folio" => $folioFinal, ":id" => $ordenId]);

  // Guardamos tipo si ya corriste el ALTER ADD COLUMN tipo
  // Si no existe la columna, este update fallará. Para evitarlo, lo intentamos y si falla no rompemos.
  try {
    $updTipo = $pdo->prepare("UPDATE ordenes_trabajo SET tipo = :tipo WHERE id = :id");
    $updTipo->execute([":tipo" => $tipo, ":id" => $ordenId]);
  } catch (Throwable $e) {
    // Si aún no agregas la columna tipo, no pasa nada por ahora.
  }

  $pdo->commit();

  respond(201, [
    "ok" => true,
    "data" => [
      "id" => $ordenId,
      "folio" => $folioFinal,
      "tipo" => $tipo,
      "estado" => "recibido"
    ]
  ]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) {
    $pdo->rollBack();
  }
  respond(500, [
    "ok" => false,
    "error" => "Error al crear orden",
    "detail" => $e->getMessage()
  ]);
}
