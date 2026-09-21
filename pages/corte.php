<?php declare(strict_types=1); ?>

<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Corte de caja</div>
      <span class="pill" id="corteFechaPill">—</span>
    </div>

    <div class="table-toolbar__right">
      <input class="input" type="date" id="corteDate" />
      <button class="btn" type="button" id="btnCargarCorte">Cargar</button>
      <button class="btn" type="button" id="btnCerrarCaja">Cerrar caja</button>
      <button class="btn" type="button" id="btnVerReporte">Reporte</button>
      <button class="btn btn--ghost" type="button" id="btnCortePdf">Descargar PDF</button>
      <button class="btn" type="button" id="btnImprimirCorte">Imprimir</button>
    </div>
  </div>

  <div class="kpis" style="margin-top:12px;">
    <div class="kpi">
      <div class="kpi__icon">💰</div>
      <div class="kpi__text">
        <div class="kpi__label">Total (pagadas)</div>
        <div class="kpi__value" id="corteTotal">$0.00</div>
        <div class="kpi__sub" id="corteNventas">0 ventas</div>
      </div>
    </div>

    <div class="kpi">
      <div class="kpi__icon">🧾</div>
      <div class="kpi__text">
        <div class="kpi__label">Subtotal / IVA</div>
        <div class="kpi__value" id="corteSubtotal">$0.00</div>
        <div class="kpi__sub" id="corteIva">$0.00 IVA</div>
      </div>
    </div>

    <div class="kpi">
      <div class="kpi__icon">⛔</div>
      <div class="kpi__text">
        <div class="kpi__label">Canceladas</div>
        <div class="kpi__value" id="corteCanceladasN">0</div>
        <div class="kpi__sub" id="corteCanceladasTotal">$0.00</div>
      </div>
    </div>

    <div class="kpi kpi--accent">
      <div class="kpi__icon">📌</div>
      <div class="kpi__text">
        <div class="kpi__label">Desglose</div>
        <div class="kpi__value" id="corteMetodoTop">—</div>
        <div class="kpi__sub">Efectivo / Tarjeta / Transferencia</div>
      </div>
    </div>
  </div>

  <div class="hr"></div>

  <table class="table" id="corteTable">
    <thead>
      <tr>
        <th>Método</th>
        <th class="num"># Ventas</th>
        <th class="num">Total</th>
      </tr>
    </thead>
    <tbody id="corteTbody">
      <tr><td colspan="3" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</div>

<style>
/* Print solo la sección del corte (si imprimes desde aquí) */
@media print{
  .table-toolbar__right{ display:none !important; }
  .nav, .sidebar, .topbar, .app__sidebar{ display:none !important; }
  .app{ padding:0 !important; }
  .table-wrap{ box-shadow:none !important; border:none !important; }
}
</style>
