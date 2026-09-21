<?php
declare(strict_types=1);

require_once __DIR__ . "/../config/db.php";

$pdo = db();

$totalServicios = (int)$pdo->query("SELECT COUNT(*) FROM servicios WHERE activo=1")->fetchColumn();

$porTipo = ['taller' => 0, 'autolavado' => 0];
$st = $pdo->query("SELECT tipo, COUNT(*) c FROM servicios WHERE activo=1 GROUP BY tipo");
foreach ($st->fetchAll(PDO::FETCH_ASSOC) as $r) {
  $t = (string)$r['tipo'];
  if (isset($porTipo[$t])) $porTipo[$t] = (int)$r['c'];
}

// Productos activos con stock en o por debajo del mínimo.
$stBajo = $pdo->query("
  SELECT nombre, stock_actual, stock_minimo
  FROM productos
  WHERE activo = 1 AND stock_actual <= stock_minimo
  ORDER BY (stock_minimo - stock_actual) DESC, nombre ASC
");
$productosBajos = $stBajo->fetchAll(PDO::FETCH_ASSOC);
$totalBajos = count($productosBajos);

$invSub = 'Todo el inventario en orden';
if ($totalBajos > 0) {
  $nombres = array_map(fn($p) => (string)$p['nombre'], array_slice($productosBajos, 0, 2));
  $invSub = implode(', ', $nombres);
  if ($totalBajos > 2) $invSub .= " y " . ($totalBajos - 2) . " más";
}

// ── Pendientes: Taller ──────────────────────────────────────────────
$totalPendTaller = (int)$pdo->query("
  SELECT COUNT(*) FROM ordenes_trabajo WHERE estado NOT IN ('entregado','cancelado')
")->fetchColumn();

$pendientesTaller = $pdo->query("
  SELECT ot.id, ot.folio, ot.estado, ot.pagado, ot.fecha_entrada,
         c.nombre_completo AS cliente_nombre,
         NULLIF(TRIM(CONCAT_WS(' ', v.marca, v.modelo)), '') AS vehiculo_desc
  FROM ordenes_trabajo ot
  INNER JOIN clientes c ON c.id = ot.cliente_id
  LEFT JOIN vehiculos v ON v.id = ot.vehiculo_id
  WHERE ot.estado NOT IN ('entregado','cancelado')
  ORDER BY ot.fecha_entrada ASC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

// ── Pendientes: Autolavado ──────────────────────────────────────────
$totalPendLavado = (int)$pdo->query("
  SELECT COUNT(*) FROM autolavado_ordenes WHERE estado <> 'entregado'
")->fetchColumn();

$pendientesLavado = $pdo->query("
  SELECT ao.id, ao.estado, ao.pagado, ao.fecha_creacion,
         s.nombre AS servicio,
         c.nombre_completo AS cliente,
         NULLIF(TRIM(CONCAT_WS(' ', v.marca, v.modelo)), '') AS vehiculo_desc
  FROM autolavado_ordenes ao
  JOIN servicios s ON s.id = ao.servicio_id
  LEFT JOIN clientes c ON c.id = ao.cliente_id
  LEFT JOIN vehiculos v ON v.id = ao.vehiculo_id
  WHERE ao.estado <> 'entregado'
  ORDER BY ao.fecha_creacion ASC
  LIMIT 6
")->fetchAll(PDO::FETCH_ASSOC);

function tiempoTranscurrido(?string $fecha): string {
  if (!$fecha) return '';
  $t = strtotime($fecha);
  if (!$t) return '';
  $diff = time() - $t;
  if ($diff < 60) return 'hace un momento';
  if ($diff < 3600) return 'hace ' . (int)floor($diff / 60) . ' min';
  if ($diff < 86400) return 'hace ' . (int)floor($diff / 3600) . ' h';
  return 'hace ' . (int)floor($diff / 86400) . ' d';
}

$ESTADO_TALLER = [
  'recibido'   => ['label' => 'Recibido',   'dot' => 'warn'],
  'en_proceso' => ['label' => 'En proceso', 'dot' => 'info'],
  'terminado'  => ['label' => 'Terminado',  'dot' => 'ok'],
];
$ESTADO_LAVADO = [
  'pendiente'  => ['label' => 'Pendiente',  'dot' => 'warn'],
  'en_proceso' => ['label' => 'En proceso', 'dot' => 'info'],
  'terminado'  => ['label' => 'Terminado',  'dot' => 'ok'],
];

// ── Ventas de los últimos 7 días (excluye canceladas, igual que el
// corte del día) ─────────────────────────────────────────────────────
$stVentas7 = $pdo->query("
  SELECT DATE(COALESCE(fecha, created_at)) AS dia, COALESCE(SUM(total),0) AS total
  FROM ventas
  WHERE DATE(COALESCE(fecha, created_at)) >= DATE_SUB(CURDATE(), INTERVAL 6 DAY)
    AND COALESCE(estado,'pagada') <> 'cancelada'
  GROUP BY DATE(COALESCE(fecha, created_at))
");
$ventasPorDia = [];
foreach ($stVentas7->fetchAll(PDO::FETCH_ASSOC) as $r) {
  $ventasPorDia[$r['dia']] = (float)$r['total'];
}
$diasEs = ['Sun' => 'Dom', 'Mon' => 'Lun', 'Tue' => 'Mar', 'Wed' => 'Mié', 'Thu' => 'Jue', 'Fri' => 'Vie', 'Sat' => 'Sáb'];
$chartLabels = [];
$chartTotales = [];
for ($i = 6; $i >= 0; $i--) {
  $d = date('Y-m-d', strtotime("-{$i} days"));
  $diaCorto = $diasEs[date('D', strtotime($d))] ?? date('D', strtotime($d));
  $chartLabels[] = $diaCorto . ' ' . date('d', strtotime($d));
  $chartTotales[] = round($ventasPorDia[$d] ?? 0, 2);
}
?>

<div class="kpis">
  <div class="kpi">
    <div class="kpi__icon">🧩</div>
    <div class="kpi__text">
      <div class="kpi__label">Servicios activos</div>
      <div class="kpi__value"><?= $totalServicios ?></div>
      <div class="kpi__sub">Taller: <?= $porTipo['taller'] ?> • Autolavado: <?= $porTipo['autolavado'] ?></div>
    </div>
  </div>

  <div class="kpi" id="kpiVentasHoy">
  <div class="kpi__icon">🧾</div>
  <div class="kpi__text">
    <div class="kpi__label">Ventas del día</div>
    <div class="kpi__value" id="homeVentasTotal">$0.00</div>
    <div class="kpi__sub" id="homeVentasCount">0 ventas</div>
  </div>
</div>

<div class="kpi" id="kpiCorteHoy">
  <div class="kpi__icon">💵</div>
  <div class="kpi__text">
    <div class="kpi__label">Corte del día</div>
    <div class="kpi__value" id="homeCorteTotal">$0.00</div>
    <div class="kpi__sub" id="homeCorteBreakdown">Efectivo $0 • Tarjeta $0 • Transferencia $0</div>
  </div>
</div>

  <a class="kpi<?= $totalBajos > 0 ? ' kpi--warn' : '' ?>" href="dashboard.php?p=inventario<?= $totalBajos > 0 ? '&bajo=1' : '' ?>" style="text-decoration:none">
    <div class="kpi__icon">📦</div>
    <div class="kpi__text">
      <div class="kpi__label">Inventario<?= $totalBajos > 0 ? ' · stock bajo' : '' ?></div>
      <div class="kpi__value"><?= $totalBajos ?></div>
      <div class="kpi__sub"><?= htmlspecialchars($invSub, ENT_QUOTES, 'UTF-8') ?></div>
    </div>
  </a>
</div>

<div class="panel">
  <div class="panel__head">
    <h3>Accesos rápidos</h3>
    <span class="badge">Atajos</span>
  </div>

  <div class="quick-grid">
    <a class="quick-tile" href="dashboard.php?p=ventas">
      <div class="quick-tile__icon">🧾</div>
      <div class="quick-tile__title">Nueva venta</div>
      <div class="quick-tile__sub">Cobro, ticket</div>
    </a>

    <a class="quick-tile" href="dashboard.php?p=taller">
      <div class="quick-tile__icon">🛠️</div>
      <div class="quick-tile__title">Taller</div>
      <div class="quick-tile__sub">Órdenes de trabajo</div>
    </a>

    <a class="quick-tile" href="dashboard.php?p=autolavado">
      <div class="quick-tile__icon">🚿</div>
      <div class="quick-tile__title">Autolavado</div>
      <div class="quick-tile__sub">Servicios</div>
    </a>

    <a class="quick-tile" href="dashboard.php?p=inventario">
      <div class="quick-tile__icon">📦</div>
      <div class="quick-tile__title">Inventario</div>
      <div class="quick-tile__sub">Productos</div>
    </a>
  </div>
</div>

<div class="hr"></div>

<div class="panel-grid" style="grid-template-columns: 1fr 1fr;">
  <div class="panel">
    <div class="panel__head">
      <h3>Pendientes · Taller</h3>
      <span class="badge<?= $totalPendTaller > 0 ? ' badge--soft' : '' ?>"><?= $totalPendTaller ?> en el taller</span>
    </div>

    <?php if (!$pendientesTaller): ?>
      <div class="muted xs">Sin órdenes pendientes en el taller 🎉</div>
    <?php else: ?>
      <div class="timeline">
        <?php foreach ($pendientesTaller as $o):
          $meta = $ESTADO_TALLER[$o['estado']] ?? ['label' => $o['estado'], 'dot' => 'info'];
        ?>
          <a class="tl-item" href="dashboard.php?p=taller" style="text-decoration:none;color:inherit;">
            <div class="tl-dot tl-dot--<?= e($meta['dot']) ?>"></div>
            <div>
              <div class="tl-title"><?= e((string)$o['cliente_nombre']) ?><?= $o['vehiculo_desc'] ? ' · ' . e((string)$o['vehiculo_desc']) : '' ?></div>
              <div class="tl-sub"><?= e($meta['label']) ?><?= $o['pagado'] ? '' : ' · sin pagar' ?><?= $o['folio'] ? ' · ' . e((string)$o['folio']) : '' ?></div>
            </div>
            <div class="tl-time"><?= e(tiempoTranscurrido($o['fecha_entrada'])) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if ($totalPendTaller > count($pendientesTaller)): ?>
        <div class="xs muted" style="margin-top:10px;">y <?= $totalPendTaller - count($pendientesTaller) ?> más — <a href="dashboard.php?p=taller">ver todas</a></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>

  <div class="panel">
    <div class="panel__head">
      <h3>Pendientes · Autolavado</h3>
      <span class="badge<?= $totalPendLavado > 0 ? ' badge--soft' : '' ?>"><?= $totalPendLavado ?> en el lavado</span>
    </div>

    <?php if (!$pendientesLavado): ?>
      <div class="muted xs">Sin pedidos pendientes en el autolavado 🎉</div>
    <?php else: ?>
      <div class="timeline">
        <?php foreach ($pendientesLavado as $o):
          $meta = $ESTADO_LAVADO[$o['estado']] ?? ['label' => $o['estado'], 'dot' => 'info'];
        ?>
          <a class="tl-item" href="dashboard.php?p=autolavado" style="text-decoration:none;color:inherit;">
            <div class="tl-dot tl-dot--<?= e($meta['dot']) ?>"></div>
            <div>
              <div class="tl-title"><?= e((string)($o['cliente'] ?: 'Sin cliente')) ?><?= $o['vehiculo_desc'] ? ' · ' . e((string)$o['vehiculo_desc']) : '' ?></div>
              <div class="tl-sub"><?= e($meta['label']) ?><?= $o['pagado'] ? '' : ' · sin pagar' ?> · <?= e((string)$o['servicio']) ?></div>
            </div>
            <div class="tl-time"><?= e(tiempoTranscurrido($o['fecha_creacion'])) ?></div>
          </a>
        <?php endforeach; ?>
      </div>
      <?php if ($totalPendLavado > count($pendientesLavado)): ?>
        <div class="xs muted" style="margin-top:10px;">y <?= $totalPendLavado - count($pendientesLavado) ?> más — <a href="dashboard.php?p=autolavado">ver todos</a></div>
      <?php endif; ?>
    <?php endif; ?>
  </div>
</div>

<div class="hr"></div>

<div class="panel">
  <div class="panel__head">
    <h3>Ventas de los últimos 7 días</h3>
    <span class="badge">Taller + Autolavado</span>
  </div>
  <div style="height:220px;">
    <canvas id="chartVentas7"></canvas>
  </div>
</div>

<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>
<script>
(() => {
  const ctx = document.getElementById('chartVentas7');
  if (!ctx || !window.Chart) return;
  new Chart(ctx, {
    type: 'bar',
    data: {
      labels: <?= json_encode($chartLabels, JSON_UNESCAPED_UNICODE) ?>,
      datasets: [{
        label: 'Ventas ($)',
        data: <?= json_encode($chartTotales) ?>,
        backgroundColor: 'rgba(213, 94, 79, .55)',
        borderColor: 'rgba(213, 94, 79, 1)',
        borderWidth: 1,
        borderRadius: 6,
        maxBarThickness: 46,
      }]
    },
    options: {
      responsive: true,
      maintainAspectRatio: false,
      plugins: { legend: { display: false } },
      scales: {
        x: { grid: { display: false }, ticks: { color: '#b6b6c6' } },
        y: { beginAtZero: true, grid: { color: 'rgba(255,255,255,.06)' }, ticks: { color: '#b6b6c6', callback: (v) => '$' + v } }
      }
    }
  });
})();
</script>
