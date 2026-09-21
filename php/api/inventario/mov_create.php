<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../includes/guard_api_admin.php";
require_once __DIR__ . "/../../../config/db.php";

header('Content-Type: application/json; charset=utf-8');

function bad(string $m, int $c=400): void {
  http_response_code($c);
  echo json_encode(['ok'=>false,'error'=>$m], JSON_UNESCAPED_UNICODE);
  exit;
}

$user = $_SESSION['user'] ?? null;
if (!$user) bad("No autenticado.", 401);

try {
  $pdo = db();

  $raw = file_get_contents("php://input");
  $d = json_decode($raw ?: "{}", true);
  if (!is_array($d)) bad("JSON inválido.");

  $producto_id = (int)($d['producto_id'] ?? 0);
  $tipo = strtoupper(trim((string)($d['tipo'] ?? '')));
  $cantidad = (int)($d['cantidad'] ?? 0);
  $referencia = trim((string)($d['referencia'] ?? ''));
  $nota = trim((string)($d['nota'] ?? ''));

  if ($producto_id <= 0) bad("Producto inválido.");
  if (!in_array($tipo, ['ENTRADA','SALIDA','AJUSTE'], true)) bad("Tipo inválido.");
  if ($cantidad === 0) bad("Cantidad no puede ser 0.");

  $uid = (int)$user['id'];

  $pdo->beginTransaction();

  // bloquear producto para actualizar stock con seguridad
  $st = $pdo->prepare("SELECT stock_actual FROM productos WHERE id=? AND activo=1 FOR UPDATE");
  $st->execute([$producto_id]);
  $p = $st->fetch(PDO::FETCH_ASSOC);
  if (!$p) bad("Producto no encontrado.", 404);

  $stock = (int)$p['stock_actual'];
  $delta = $cantidad;

  if ($tipo === 'SALIDA') {
    $delta = -abs($cantidad);
    if ($stock + $delta < 0) bad("Stock insuficiente. Stock actual: $stock", 409);
  } elseif ($tipo === 'ENTRADA') {
    $delta = abs($cantidad);
  } else { // AJUSTE
    // puede ser positivo o negativo, tú decides
    $delta = $cantidad;
    if ($stock + $delta < 0) bad("Ajuste dejaría stock negativo. Stock actual: $stock", 409);
  }

  $ins = $pdo->prepare("
    INSERT INTO inventario_movimientos (producto_id, tipo, cantidad, referencia, nota, usuario_id)
    VALUES (?,?,?,?,?,?)
  ");
  $ins->execute([
    $producto_id,
    $tipo,
    $delta, // guardamos el delta real (+ / -)
    $referencia !== '' ? $referencia : null,
    $nota !== '' ? $nota : null,
    $uid
  ]);

  $up = $pdo->prepare("UPDATE productos SET stock_actual = stock_actual + ?, updated_at=NOW() WHERE id=?");
  $up->execute([$delta, $producto_id]);

  $pdo->commit();

  echo json_encode(['ok'=>true], JSON_UNESCAPED_UNICODE);
} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
