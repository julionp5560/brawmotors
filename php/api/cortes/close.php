<?php
declare(strict_types=1);

require_once __DIR__ . "/../../../includes/guard_api.php";
require_once __DIR__ . "/../../../config/db.php";

header('Content-Type: application/json; charset=utf-8');


function bad(string $m, int $c=400): void {
  http_response_code($c);
  echo json_encode(['ok'=>false,'error'=>$m], JSON_UNESCAPED_UNICODE);
  exit;
}

$user = $_SESSION['user'] ?? null;
if (!$user) bad("No autenticado.", 401);

$raw = file_get_contents("php://input");
$data = json_decode($raw ?: "{}", true);
if (!is_array($data)) bad("JSON inválido.");

$date = preg_replace('/[^0-9\-]/', '', (string)($data['date'] ?? date('Y-m-d')));
if ($date === '') $date = date('Y-m-d');

try {
  $pdo = db();
  $pdo->beginTransaction();

  // ¿Ya existe corte de ese día?
  $chk = $pdo->prepare("SELECT id, folio FROM cortes WHERE fecha=? LIMIT 1");
  $chk->execute([$date]);
  $ex = $chk->fetch(PDO::FETCH_ASSOC);
  if ($ex) bad("Ya existe un corte para $date (folio {$ex['folio']}).", 409);

  // Totales pagadas NO canceladas y no incluidas en corte aún
  $st = $pdo->prepare("
    SELECT
      COUNT(*) AS n_ventas,
      COALESCE(SUM(subtotal),0) AS subtotal,
      COALESCE(SUM(iva),0) AS iva,
      COALESCE(SUM(total),0) AS total
    FROM ventas
    WHERE DATE(COALESCE(fecha, created_at)) = ?
      AND COALESCE(estado,'pagada') <> 'cancelada'
      AND corte_id IS NULL
  ");
  $st->execute([$date]);
  $r = $st->fetch(PDO::FETCH_ASSOC) ?: ['n_ventas'=>0,'subtotal'=>0,'iva'=>0,'total'=>0];

  // Canceladas del día (info)
  $stC = $pdo->prepare("
    SELECT
      COUNT(*) AS n_canceladas,
      COALESCE(SUM(total),0) AS total_cancelado
    FROM ventas
    WHERE DATE(COALESCE(fecha, created_at)) = ?
      AND COALESCE(estado,'pagada') = 'cancelada'
  ");
  $stC->execute([$date]);
  $c = $stC->fetch(PDO::FETCH_ASSOC) ?: ['n_canceladas'=>0,'total_cancelado'=>0];

  $uid = (int)$user['id'];

  // Crear corte
  $ins = $pdo->prepare("
    INSERT INTO cortes (folio, fecha, cerrado_at, cerrado_por, n_ventas, subtotal, iva, total, n_canceladas, total_cancelado)
    VALUES ('TMP', ?, NOW(), ?, ?, ?, ?, ?, ?, ?)
  ");
  $ins->execute([
    $date, $uid,
    (int)$r['n_ventas'], (float)$r['subtotal'], (float)$r['iva'], (float)$r['total'],
    (int)$c['n_canceladas'], (float)$c['total_cancelado'],
  ]);

  $corte_id = (int)$pdo->lastInsertId();
  $folio = sprintf("C-%s-%06d", str_replace('-', '', $date), $corte_id);

  $pdo->prepare("UPDATE cortes SET folio=? WHERE id=?")->execute([$folio, $corte_id]);

  // Desglose por método (solo pagadas, sin corte aún)
  $st2 = $pdo->prepare("
    SELECT LOWER(COALESCE(metodo_pago,'efectivo')) AS metodo_pago,
           COUNT(*) AS n,
           COALESCE(SUM(total),0) AS total
    FROM ventas
    WHERE DATE(COALESCE(fecha, created_at)) = ?
      AND COALESCE(estado,'pagada') <> 'cancelada'
      AND corte_id IS NULL
    GROUP BY LOWER(COALESCE(metodo_pago,'efectivo'))
  ");
  $st2->execute([$date]);
  $rows = $st2->fetchAll(PDO::FETCH_ASSOC);

  $map = ['efectivo'=>['n'=>0,'total'=>0],'tarjeta'=>['n'=>0,'total'=>0],'transferencia'=>['n'=>0,'total'=>0]];
  foreach ($rows as $x) {
    $m = (string)$x['metodo_pago'];
    if (!isset($map[$m])) continue;
    $map[$m] = ['n'=>(int)$x['n'],'total'=>(float)$x['total']];
  }

  $insD = $pdo->prepare("INSERT INTO corte_detalle (corte_id, metodo_pago, n, total) VALUES (?,?,?,?)");
  foreach (['efectivo','tarjeta','transferencia'] as $m) {
    $insD->execute([$corte_id, $m, $map[$m]['n'], $map[$m]['total']]);
  }

  // Amarrar ventas al corte (congelar)
  $upVentas = $pdo->prepare("
    UPDATE ventas
    SET corte_id = ?
    WHERE DATE(COALESCE(fecha, created_at)) = ?
      AND COALESCE(estado,'pagada') <> 'cancelada'
      AND corte_id IS NULL
  ");
  $upVentas->execute([$corte_id, $date]);

  $pdo->commit();

  echo json_encode([
    'ok'=>true,
    'corte_id'=>$corte_id,
    'folio'=>$folio
  ], JSON_UNESCAPED_UNICODE);

} catch (Throwable $e) {
  if (isset($pdo) && $pdo->inTransaction()) $pdo->rollBack();
  http_response_code(500);
  echo json_encode(['ok'=>false,'error'=>$e->getMessage()], JSON_UNESCAPED_UNICODE);
}
