<?php declare(strict_types=1); ?>

<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Reportes</div>
      <span class="pill" id="repRangoPill">—</span>
    </div>
    <div class="table-toolbar__right">
      <input class="input" type="date" id="repDesde" />
      <input class="input" type="date" id="repHasta" />
      <button class="btn" type="button" id="btnGenerarReporte">Generar</button>
      <button class="btn btn--ghost" type="button" id="btnReporteVentasPdf">Descargar PDF</button>
      <button class="btn btn--ghost" type="button" onclick="window.print()">Imprimir</button>
    </div>
  </div>

  <div class="kpis" style="margin-top:12px;">
    <div class="kpi">
      <div class="kpi__icon">🧾</div>
      <div class="kpi__text">
        <div class="kpi__label">Ventas (mostrador)</div>
        <div class="kpi__value" id="repVentasTotal">$0.00</div>
        <div class="kpi__sub" id="repVentasN">0 ventas</div>
      </div>
    </div>

    <div class="kpi">
      <div class="kpi__icon">🛠️</div>
      <div class="kpi__text">
        <div class="kpi__label">Taller (entregadas)</div>
        <div class="kpi__value" id="repTallerTotal">$0.00</div>
        <div class="kpi__sub" id="repTallerN">0 órdenes</div>
      </div>
    </div>

    <div class="kpi kpi--accent">
      <div class="kpi__icon">💰</div>
      <div class="kpi__text">
        <div class="kpi__label">Total general</div>
        <div class="kpi__value" id="repTotalGeneral">$0.00</div>
        <div class="kpi__sub">Ventas + taller entregado</div>
      </div>
    </div>
  </div>

  <div class="hr"></div>

  <h3 style="margin:8px 0 10px;">Ventas por día</h3>
  <table class="table">
    <thead><tr><th>Fecha</th><th class="num"># Ventas</th><th class="num">Total</th></tr></thead>
    <tbody id="repSerieTbody"><tr><td colspan="3" class="muted">Elige un rango y presiona Generar.</td></tr></tbody>
  </table>

  <div class="hr"></div>

  <h3 style="margin:8px 0 10px;">Top servicios / productos</h3>
  <table class="table">
    <thead><tr><th>Descripción</th><th class="num">Cant</th><th class="num">Total</th></tr></thead>
    <tbody id="repTopServiciosTbody"><tr><td colspan="3" class="muted">—</td></tr></tbody>
  </table>

  <div class="hr"></div>

  <h3 style="margin:8px 0 10px;">Top clientes</h3>
  <table class="table">
    <thead><tr><th>Cliente</th><th class="num">Visitas</th><th class="num">Total</th></tr></thead>
    <tbody id="repTopClientesTbody"><tr><td colspan="3" class="muted">—</td></tr></tbody>
  </table>
</div>

<style>
@media print{
  .table-toolbar__right{ display:none !important; }
  .nav, .sidebar, .topbar, .app__sidebar{ display:none !important; }
  .app{ padding:0 !important; }
  .table-wrap{ box-shadow:none !important; border:none !important; }
}
</style>
