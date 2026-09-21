<?php
declare(strict_types=1); ?>
<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Reporte del día</div>
      <span class="pill" id="repFecha">—</span>
    </div>
    <div class="table-toolbar__right">
      <button class="btn" type="button" onclick="window.print()">Imprimir</button>
    </div>
  </div>

  <div class="kpis" style="margin-top:12px;">
    <div class="kpi">
      <div class="kpi__icon">🧾</div>
      <div class="kpi__text">
        <div class="kpi__label">Total cobrado</div>
        <div class="kpi__value" id="repTotal">$0.00</div>
        <div class="kpi__sub" id="repN">0 ventas</div>
      </div>
    </div>
  </div>

  <div class="hr"></div>

  <h3 style="margin:8px 0 10px;">Top servicios</h3>
  <table class="table">
    <thead><tr><th>Servicio</th><th class="num">Cant</th><th class="num">Total</th></tr></thead>
    <tbody id="repTop"><tr><td colspan="3" class="muted">Cargando…</td></tr></tbody>
  </table>

  <div class="hr"></div>

  <h3 style="margin:8px 0 10px;">Ventas</h3>
  <table class="table">
    <thead><tr><th>Folio</th><th>Hora</th><th>Cliente</th><th>Pago</th><th class="num">Total</th><th>Estado</th></tr></thead>
    <tbody id="repVentas"><tr><td colspan="6" class="muted">Cargando…</td></tr></tbody>
  </table>
</div>

<style>
@media print{
  .table-toolbar__right{ display:none !important; }
}
</style>
