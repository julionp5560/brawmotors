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

function body(): array {
  $raw = file_get_contents('php://input');
  $data = json_decode($raw ?: '', true);
  return is_array($data) ? $data : [];
}

try {
  $pdo = db();
  $data = body();

  $orden_id = isset($data['orden_id']) ? (int)$data['orden_id'] : 0;
  $servicio_id = isset($data['servicio_id']) ? (int)$data['servicio_id'] : 0;
  $cantidad = isset($data['cantidad']) ? (int)$data['cantidad'] : 1;

  if ($orden_id <= 0) respond(400, ["ok"=>false, "error"=>"orden_id requerido"]);
  if ($servicio_id <= 0) respond(400, ["ok"=>false, "error"=>"servicio_id requerido"]);
  if ($cantidad < 1) $cantidad = 1;

  $pdo->beginTransaction();

  // Validar orden existe
  $st = $pdo->prepare("SELECT id FROM ordenes_trabajo WHERE id = ? LIMIT 1");
  $st->execute([$orden_id]);
  if (!$st->fetch()) {
    $pdo->rollBack();
    respond(404, ["ok"=>false, "error"=>"Orden no existe"]);
  }

  // Tomar precio del servicio
  $st = $pdo->prepare("SELECT id, nombre, precio, activo FROM servicios WHERE id = ? LIMIT 1");
  $st->execute([$servicio_id]);
  $svc = $st->fetch();
  if (!$svc) {
    $pdo->rollBack();
    respond(404, ["ok"=>false, "error"=>"Servicio no existe"]);
  }
  if ((int)$svc['activo'] !== 1) {
    $pdo->rollBack();
    respond(400, ["ok"=>false, "error"=>"Servicio inactivo"]);
  }

  // Precio "desde"/a cotizar: caja/admin puede ajustar el monto al agregar
  // el servicio a la orden. Un operario (Mecánico) nunca puede mandar este
  // ajuste — sigue sin manejar dinero.
  $precio_unit = (float)$svc['precio'];
  if (!is_operario() && array_key_exists('precio_unitario', $data)) {
    $p = (float)$data['precio_unitario'];
    if ($p >= 0) $precio_unit = $p;
  }
  $subtotal = $precio_unit * $cantidad;

  // Insertar renglón
  $ins = $pdo->prepare("
    INSERT INTO orden_servicios (orden_id, servicio_id, cantidad, precio_unitario, subtotal)
    VALUES (:orden_id, :servicio_id, :cantidad, :precio_unitario, :subtotal)
  ");
  $ins->execute([
    ":orden_id" => $orden_id,
    ":servicio_id" => $servicio_id,
    ":cantidad" => $cantidad,
    ":precio_unitario" => $precio_unit,
    ":subtotal" => $subtotal
  ]);

  $linea_id = (int)$pdo->lastInsertId();

  // Recalcular total orden = servicios + productos
  $sumServ = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_servicios WHERE orden_id = {$orden_id}")
                        ->fetchColumn();

  $sumProd = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_productos WHERE orden_id = {$orden_id}")
                        ->fetchColumn();

  $total = $sumServ + $sumProd;

  $upd = $pdo->prepare("UPDATE ordenes_trabajo SET total = :total WHERE id = :id");
  $upd->execute([":total" => $total, ":id" => $orden_id]);

  $pdo->commit();

  $data_out = [
    "linea_id" => $linea_id,
    "orden_id" => $orden_id,
    "servicio" => ["id" => (int)$svc['id'], "nombre" => $svc['nombre']],
    "cantidad" => $cantidad,
  ];

  // Rol Mecánico (app móvil): nunca exponer precios/montos.
  if (!is_operario()) {
    $data_out["precio_unitario"] = number_format($precio_unit, 2, '.', '');
    $data_out["subtotal"] = number_format($subtotal, 2, '.', '');
    $data_out["total_orden"] = number_format($total, 2, '.', '');
  }

  respond(201, ["ok" => true, "data" => $data_out]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  respond(500, ["ok"=>false, "error"=>"Error al agregar servicio", "detail"=>$e->getMessage()]);
}
