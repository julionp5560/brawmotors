<?php
declare(strict_types=1);

require_once __DIR__ . "/includes/guard.php";
require_once __DIR__ . "/config/db.php";

$id = (int)($_GET['id'] ?? 0);
if ($id <= 0) {
  http_response_code(400);
  echo "Falta id de venta.";
  exit;
}

$pdo = db();

$st = $pdo->prepare("SELECT v.*, u.nombre_completo AS cajero_nombre
                     FROM ventas v
                     LEFT JOIN usuarios u ON u.id = v.usuario_id
                     WHERE v.id=? LIMIT 1");
$st->execute([$id]);
$venta = $st->fetch(PDO::FETCH_ASSOC);

if (!$venta) {
  http_response_code(404);
  echo "Venta no encontrada.";
  exit;
}

$dt = $pdo->prepare("SELECT descripcion, cantidad, precio_unitario, importe
                     FROM venta_detalle WHERE venta_id=? ORDER BY id ASC");
$dt->execute([$id]);
$items = $dt->fetchAll(PDO::FETCH_ASSOC);

function money(float $n): string { return number_format($n, 2, '.', ','); }

global $NEGOCIO_NOMBRE, $NEGOCIO_SUCURSAL, $NEGOCIO_DIRECCION, $NEGOCIO_TELEFONO, $TICKET_ANCHO_MM;
$negocio = $NEGOCIO_NOMBRE ?? "BRAW MOTORS";
$sucursal = $NEGOCIO_SUCURSAL ?? "Punto de venta";
$direccion = $NEGOCIO_DIRECCION ?? "Ciudad Guzmán, Jal.";
$telefono = $NEGOCIO_TELEFONO ?? "---";
$anchoMm = (int)($TICKET_ANCHO_MM ?? 80);
?>
<!doctype html>
<html lang="es">
<head>
  <meta charset="utf-8" />
  <meta name="viewport" content="width=device-width, initial-scale=1" />
  <title>Ticket <?= htmlspecialchars((string)$venta['folio']) ?></title>
  <style>
    :root{ --w: <?= $anchoMm ?>mm; }
    html,body{ margin:0; padding:0; background:#fff; color:#000; }
    body{ font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, "Liberation Mono", monospace; }
    /* Alineado a la izquierda (no centrado): si el navegador/driver de la
       impresora no respeta el @page size exacto, un ticket centrado se
       recorre y se corta de un lado. Pegado al borde izquierdo nunca se
       pierde contenido, en el peor caso sobra papel a la derecha. */
    .ticket{ width: var(--w); max-width: 100%; margin: 0; padding: 8px 6px; box-sizing: border-box; }
    .center{ text-align:center; }
    .muted{ color:#333; }
    .h1{ font-size: <?= $anchoMm <= 58 ? 14 : 16 ?>px; font-weight: 900; letter-spacing:.3px; }
    .small{ font-size: <?= $anchoMm <= 58 ? 11 : 12 ?>px; }
    .xs{ font-size: <?= $anchoMm <= 58 ? 10 : 11 ?>px; }
    .hr{ border-top: 1px dashed #000; margin: 6px 0; }
    .row{ display:flex; justify-content:space-between; gap: 6px; }
    .wrap{ white-space: normal; word-break: break-word; }
    table{ width:100%; border-collapse: collapse; table-layout: fixed; }
    th, td{ font-size: <?= $anchoMm <= 58 ? 11 : 12 ?>px; padding: 4px 0; vertical-align: top; overflow-wrap: break-word; }
    th{ text-align:left; border-bottom: 1px dashed #000; padding-bottom: 6px; }
    td.num, th.num{ text-align:right; width: 30%; }
    .total{ font-size: <?= $anchoMm <= 58 ? 13 : 14 ?>px; font-weight: 900; }
    .btns{ width: var(--w); max-width:100%; margin: 10px 0 0; display:flex; gap: 8px; justify-content:center; box-sizing: border-box; }
    button{ font-family: inherit; padding: 8px 10px; border: 1px solid #000; background:#fff; cursor:pointer; }
    @media print { .btns{ display:none !important; } .ticket{ padding: 0; } @page { size: <?= $anchoMm ?>mm auto; margin: 0; } }
  </style>
</head>
<body>

  <div class="ticket">
    <div class="center">
      <div class="h1"><?= htmlspecialchars($negocio) ?></div>
      <div class="small"><?= htmlspecialchars($sucursal) ?></div>
      <div class="xs muted"><?= htmlspecialchars($direccion) ?></div>
      <div class="xs muted"><?= htmlspecialchars($telefono) ?></div>
    </div>

    <div class="hr"></div>

    <div class="xs">
      <div class="row"><span>Folio:</span><span><?= htmlspecialchars((string)$venta['folio']) ?></span></div>
      <div class="row"><span>Fecha:</span><span><?= htmlspecialchars((string)$venta['fecha']) ?></span></div>
      <div class="row"><span>Cajero:</span><span class="wrap"><?= htmlspecialchars((string)($venta['cajero_nombre'] ?? '')) ?></span></div>
      <div class="row"><span>Cliente:</span><span class="wrap"><?= htmlspecialchars((string)$venta['cliente_nombre']) ?></span></div>
      <div class="row"><span>Pago:</span><span><?= htmlspecialchars((string)$venta['metodo_pago']) ?></span></div>
      <div class="row"><span>Estado:</span><span><?= htmlspecialchars((string)$venta['estado']) ?></span></div>
    </div>

    <div class="hr"></div>

    <table>
      <thead>
        <tr>
          <th>Desc</th>
          <th class="num">Imp</th>
        </tr>
      </thead>
      <tbody>
        <?php foreach ($items as $it): ?>
          <?php
            $desc = (string)$it['descripcion'];
            $qty = (float)$it['cantidad'];
            $price = (float)$it['precio_unitario'];
            $line = (float)$it['importe'];
          ?>
          <tr>
            <td class="wrap">
              <?= htmlspecialchars($desc) ?><br>
              <span class="xs muted"><?= money($qty) ?> x $<?= money($price) ?></span>
            </td>
            <td class="num">$<?= money($line) ?></td>
          </tr>
        <?php endforeach; ?>
      </tbody>
    </table>

    <div class="hr"></div>

    <div class="xs">
      <div class="row"><span>Subtotal:</span><span>$<?= money((float)$venta['subtotal']) ?></span></div>
      <div class="row"><span>IVA (<?= money((float)$venta['iva_rate']) ?>%):</span><span>$<?= money((float)$venta['iva']) ?></span></div>
      <div class="row total"><span>TOTAL:</span><span>$<?= money((float)$venta['total']) ?></span></div>
    </div>

    <?php if (!empty($venta['notas'])): ?>
      <div class="hr"></div>
      <div class="xs wrap"><b>Notas:</b> <?= htmlspecialchars((string)$venta['notas']) ?></div>
    <?php endif; ?>

    <div class="hr"></div>

    <div class="center xs muted">
      Gracias por su preferencia 🙌<br>
      Vuelva pronto
    </div>
  </div>

  <div class="btns">
    <button type="button" onclick="window.print()">Imprimir</button>
    <button type="button" onclick="window.close()">Cerrar</button>
  </div>
</body>
</html>

