<?php
declare(strict_types=1);

header('Content-Type: application/json; charset=utf-8');
require_once dirname(__DIR__, 3) . '/includes/guard_api.php';
require_once dirname(__DIR__, 3) . '/config/db.php';
require_once dirname(__DIR__, 3) . '/includes/functions.php';

// La app móvil (roles Mecánico/Lavador) no maneja refacciones/inventario:
// eso implica precios y descuenta stock, así que es exclusivo de caja/admin
// desde la PC principal.
if (is_operario()) {
  http_response_code(403);
  echo json_encode(['ok' => false, 'error' => 'No autorizado: agrega refacciones desde la PC principal.'], JSON_UNESCAPED_UNICODE);
  exit;
}

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

  $orden_id    = isset($data['orden_id']) ? (int)$data['orden_id'] : 0;
  $producto_id = isset($data['producto_id']) ? (int)$data['producto_id'] : 0;
  $cantidad    = isset($data['cantidad']) ? (int)$data['cantidad'] : 1;

  // Si lo mandas desde el frontend/Postman, lo usa; si no, lo toma de la orden.
  $usuario_id_input = isset($data['usuario_id']) ? (int)$data['usuario_id'] : 0;

  if ($orden_id <= 0) respond(400, ["ok"=>false, "error"=>"orden_id requerido"]);
  if ($producto_id <= 0) respond(400, ["ok"=>false, "error"=>"producto_id requerido"]);
  if ($cantidad < 1) $cantidad = 1;

  $pdo->beginTransaction();

  // 1) Validar orden + obtener usuario_id de la orden
  $st = $pdo->prepare("SELECT id, usuario_id FROM ordenes_trabajo WHERE id = ? LIMIT 1");
  $st->execute([$orden_id]);
  $ord = $st->fetch();

  if (!$ord) {
    $pdo->rollBack();
    respond(404, ["ok"=>false, "error"=>"Orden no existe"]);
  }

  $usuario_id_orden = (int)($ord['usuario_id'] ?? 0);
  $usuario_id = $usuario_id_input > 0 ? $usuario_id_input : $usuario_id_orden;

  if ($usuario_id <= 0) {
    $pdo->rollBack();
    respond(400, ["ok"=>false, "error"=>"La orden no tiene usuario_id y no mandaste usuario_id"]);
  }

  // Verificar que el usuario exista (para evitar FK)
  $st = $pdo->prepare("SELECT id FROM usuarios WHERE id = ? LIMIT 1");
  $st->execute([$usuario_id]);
  if (!$st->fetch()) {
    $pdo->rollBack();
    respond(400, ["ok"=>false, "error"=>"usuario_id no existe en usuarios"]);
  }

  // 2) Obtener producto (con lock para stock)
  $st = $pdo->prepare("
    SELECT id, nombre, precio_venta, stock_actual, activo
    FROM productos
    WHERE id = ? LIMIT 1
    FOR UPDATE
  ");
  $st->execute([$producto_id]);
  $prod = $st->fetch();

  if (!$prod) {
    $pdo->rollBack();
    respond(404, ["ok"=>false, "error"=>"Producto no existe"]);
  }
  if ((int)$prod['activo'] !== 1) {
    $pdo->rollBack();
    respond(400, ["ok"=>false, "error"=>"Producto inactivo"]);
  }
  if ((int)$prod['stock_actual'] < $cantidad) {
    $pdo->rollBack();
    respond(400, ["ok"=>false, "error"=>"Stock insuficiente"]);
  }

  $precio_unit = (float)$prod['precio_venta'];
  $subtotal = $precio_unit * $cantidad;

  // 3) Insertar producto en la orden
  $ins = $pdo->prepare("
    INSERT INTO orden_productos
      (orden_id, producto_id, cantidad, precio_unitario, subtotal)
    VALUES
      (:orden_id, :producto_id, :cantidad, :precio_unitario, :subtotal)
  ");
  $ins->execute([
    ":orden_id" => $orden_id,
    ":producto_id" => $producto_id,
    ":cantidad" => $cantidad,
    ":precio_unitario" => $precio_unit,
    ":subtotal" => $subtotal
  ]);

  $linea_id = (int)$pdo->lastInsertId();

  // 4) Descontar stock
  $upd = $pdo->prepare("
    UPDATE productos
    SET stock_actual = stock_actual - :cant
    WHERE id = :id
  ");
  $upd->execute([
    ":cant" => $cantidad,
    ":id" => $producto_id
  ]);

  // 5) Movimiento de inventario (YA incluye usuario_id)
  $mov = $pdo->prepare("
    INSERT INTO movimientos_inventario
      (producto_id, tipo, cantidad, referencia_tipo, referencia_id, nota, usuario_id)
    VALUES
      (:producto_id, 'salida', :cantidad, 'orden_trabajo', :orden_id, 'Uso en orden de trabajo', :usuario_id)
  ");
  $mov->execute([
    ":producto_id" => $producto_id,
    ":cantidad" => $cantidad,
    ":orden_id" => $orden_id,
    ":usuario_id" => $usuario_id
  ]);

  // 6) Recalcular total
  $sumServ = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_servicios WHERE orden_id = {$orden_id}")
                        ->fetchColumn();
  $sumProd = (float)$pdo->query("SELECT COALESCE(SUM(subtotal),0) FROM orden_productos WHERE orden_id = {$orden_id}")
                        ->fetchColumn();
  $total = $sumServ + $sumProd;

  $pdo->prepare("UPDATE ordenes_trabajo SET total = :total WHERE id = :id")
      ->execute([":total" => $total, ":id" => $orden_id]);

  $pdo->commit();

  respond(201, [
    "ok" => true,
    "data" => [
      "linea_id" => $linea_id,
      "orden_id" => $orden_id,
      "producto" => ["id" => (int)$prod['id'], "nombre" => $prod['nombre']],
      "cantidad" => $cantidad,
      "precio_unitario" => number_format($precio_unit, 2, '.', ''),
      "subtotal" => number_format($subtotal, 2, '.', ''),
      "total_orden" => number_format($total, 2, '.', ''),
      "usuario_id_movimiento" => $usuario_id
    ]
  ]);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo instanceof PDO && $pdo->inTransaction()) $pdo->rollBack();
  respond(500, ["ok"=>false, "error"=>"Error al agregar producto", "detail"=>$e->getMessage()]);
}
