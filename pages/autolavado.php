<?php declare(strict_types=1); ?>
<!-- pages/autolavado.php — POS táctil tipo McDonald's -->
<style>
/* ── POS layout ── */
.pos {
  display: grid;
  grid-template-columns: 1fr 340px;
  gap: 14px;
  height: calc(100vh - 120px);
  overflow: hidden;
}

/* Panel servicios */
.pos__servicios {
  overflow-y: auto;
  padding-right: 4px;
}
.pos__label {
  font-size: 11px;
  font-weight: 900;
  letter-spacing: 2px;
  text-transform: uppercase;
  color: var(--muted);
  margin-bottom: 10px;
}
.srv-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(160px, 1fr));
  gap: 10px;
}
.srv-btn {
  background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 18px 14px;
  cursor: pointer;
  text-align: left;
  color: var(--text);
  transition: transform .12s ease, border-color .12s ease, box-shadow .12s ease;
  position: relative;
  overflow: hidden;
}
.srv-btn:hover {
  transform: translateY(-2px);
  border-color: rgba(213,94,79,.5);
  box-shadow: 0 8px 24px rgba(165,39,39,.2);
}
.srv-btn:active { transform: translateY(0); }
.srv-btn.is-sel {
  border-color: var(--primary2);
  background: linear-gradient(180deg, rgba(165,39,39,.22), rgba(165,39,39,.08));
  box-shadow: 0 8px 24px rgba(165,39,39,.25);
}
.srv-btn__badge {
  position: absolute;
  top: 10px; right: 10px;
  background: var(--primary2);
  color: #fff;
  border-radius: 50%;
  width: 20px; height: 20px;
  display: none;
  align-items: center; justify-content: center;
  font-size: 11px; font-weight: 900;
}
.srv-btn.is-sel .srv-btn__badge { display: flex; }
.srv-btn__name {
  font-size: 13px;
  font-weight: 900;
  line-height: 1.25;
  margin-bottom: 6px;
}
.srv-btn__desc {
  font-size: 11px;
  color: var(--muted);
  line-height: 1.4;
  margin-bottom: 10px;
}
.srv-btn__precio {
  font-size: 18px;
  font-weight: 950;
  color: var(--primary2);
}
.srv-btn__tiempo {
  font-size: 11px;
  color: var(--muted);
  margin-top: 3px;
}
.srv-btn__hint {
  font-size: 11px;
  color: var(--muted);
  margin-top: 8px;
}

/* Sub-selector de tamaño (dentro del mismo botón de grupo) */
.srv-btn__tallas {
  display: flex;
  flex-wrap: wrap;
  gap: 6px;
  margin-top: 10px;
}
.talla-btn {
  border: 1px solid var(--border);
  background: rgba(255,255,255,.06);
  color: var(--text);
  border-radius: 10px;
  padding: 6px 10px;
  font-size: 11px;
  font-weight: 800;
  cursor: pointer;
  text-align: left;
}
.talla-btn:hover { border-color: rgba(213,94,79,.5); }
.talla-btn__precio { color: var(--primary2); font-weight: 950; margin-left: 4px; }
[data-tema="alto_contraste"] .talla-btn { background:#ffffff; }

/* Toggle de extras */
.extras-toggle {
  width: 100%;
  margin-top: 14px;
  border: 1px dashed var(--border);
  background: transparent;
  color: var(--muted);
  border-radius: 14px;
  padding: 10px;
  font-weight: 800;
  font-size: 12px;
  cursor: pointer;
  text-align: center;
}
.extras-toggle:hover { border-color: rgba(213,94,79,.5); color: var(--text); }

/* Cargo personalizado */
.cargo-form {
  margin-top: 8px;
  border: 1px solid var(--border);
  border-radius: 14px;
  padding: 12px;
  background: rgba(255,255,255,.03);
}
.cargo-form__label { font-size: 12px; color: var(--muted); font-weight: 800; margin-bottom: 6px; }
.cargo-form__desc {
  width: 100%; box-sizing: border-box; border: 1px solid var(--border); background: rgba(255,255,255,.05);
  color: var(--text); border-radius: 10px; padding: 9px 10px; font-size: 13px; margin-bottom: 8px;
}
.cargo-form__monto-wrap { display:flex; align-items:center; gap:6px; margin-bottom: 10px; }
.cargo-form__monto {
  flex: 1; box-sizing: border-box; border: 1px solid var(--border); background: rgba(255,255,255,.05);
  color: var(--text); border-radius: 10px; padding: 9px 10px; font-size: 13px;
}
.cargo-form__hint { font-size: 11px; color: var(--muted); margin-top: -4px; margin-bottom: 10px; }
.cargo-form__btns { display: flex; gap: 8px; }
[data-tema="alto_contraste"] .cargo-form { background: #ffffff; }
[data-tema="alto_contraste"] .cargo-form__desc,
[data-tema="alto_contraste"] .cargo-form__monto { background: #ffffff; }

/* Panel cobro */
.pos__cobro {
  background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  border: 1px solid var(--border);
  border-radius: 20px;
  display: flex;
  flex-direction: column;
  overflow: hidden;
}
.pos__cobro-scroll {
  flex: 1;
  overflow-y: auto;
  padding: 14px;
}

/* Búsqueda cliente inline */
.autocomplete-wrap { position: relative; }
.autocomplete-drop {
  position: absolute;
  top: 100%; left: 0; right: 0;
  background: var(--panel);
  border: 1px solid var(--border);
  border-top: none;
  border-radius: 0 0 14px 14px;
  overflow: hidden;
  z-index: 50;
  display: none;
}
.autocomplete-drop.open { display: block; }
.ac-item {
  padding: 10px 12px;
  cursor: pointer;
  font-size: 13px;
  border-bottom: 1px solid var(--border);
  transition: background .1s;
}
.ac-item:last-child { border-bottom: 0; }
.ac-item:hover { background: rgba(255,255,255,.06); }
.ac-item small { color: var(--muted); }

/* Cliente seleccionado chip */
.cliente-chip {
  display: none;
  align-items: center;
  gap: 10px;
  border: 1px solid rgba(213,94,79,.45);
  background: rgba(165,39,39,.1);
  border-radius: 14px;
  padding: 10px 12px;
}
.cliente-chip.show { display: flex; }
.cliente-chip__info { flex: 1; }
.cliente-chip__name { font-weight: 900; font-size: 13px; }
.cliente-chip__tel  { font-size: 11px; color: var(--muted); }
.cliente-chip__clear {
  border: 1px solid var(--border);
  background: rgba(255,255,255,.05);
  color: var(--muted);
  border-radius: 10px;
  padding: 6px 9px;
  cursor: pointer;
  font-size: 12px;
}
.cliente-chip__clear:hover { border-color: rgba(255,90,90,.5); color: #ff5a5a; }

/* Botón dashed para acciones secundarias */
.btn-dashed {
  width: 100%;
  background: none;
  border: 1px dashed var(--border);
  border-radius: 12px;
  color: var(--muted);
  padding: 9px 12px;
  cursor: pointer;
  font-size: 12px;
  text-align: left;
  transition: border-color .12s, color .12s;
  margin-top: 6px;
}
.btn-dashed:hover { border-color: var(--primary2); color: var(--primary2); }

/* Vehículos */
.vh-list { display: flex; flex-direction: column; gap: 6px; }
.vh-btn {
  background: rgba(255,255,255,.04);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 8px 12px;
  cursor: pointer;
  color: var(--text);
  text-align: left;
  font-size: 12px;
  transition: border-color .12s;
}
.vh-btn:hover, .vh-btn.is-sel { border-color: var(--primary2); background: rgba(165,39,39,.1); }
.vh-btn__label { font-weight: 700; }
.vh-btn__placa { color: var(--muted); font-size: 11px; }

/* Orden resumen */
.orden-lista { display: flex; flex-direction: column; gap: 6px; }
.orden-item {
  display: flex;
  align-items: center;
  gap: 8px;
  background: rgba(0,0,0,.18);
  border: 1px solid var(--border);
  border-radius: 12px;
  padding: 8px 10px;
  font-size: 13px;
}
.orden-item__name { flex: 1; font-weight: 700; }
.orden-item__precio { color: var(--primary2); font-weight: 900; white-space: nowrap; }
.orden-item__precio-wrap { color: var(--primary2); font-weight: 900; white-space: nowrap; display:flex; align-items:center; gap:2px; }
.orden-item__precio-inp {
  width: 72px;
  border: 1px solid var(--border);
  background: rgba(255,255,255,.05);
  color: var(--primary2);
  font-weight: 900;
  border-radius: 8px;
  padding: 3px 6px;
  text-align: right;
  font-size: 13px;
}
[data-tema="alto_contraste"] .orden-item__precio-inp { background:#ffffff; }
.orden-item__rm {
  border: none;
  background: none;
  color: var(--muted);
  cursor: pointer;
  padding: 2px 5px;
  font-size: 13px;
}
.orden-item__rm:hover { color: #ff5a5a; }
.orden-empty { text-align: center; color: var(--muted); padding: 18px 0; font-size: 13px; }

/* Total */
.total-row {
  display: flex;
  justify-content: space-between;
  align-items: center;
  padding: 12px 0;
  border-top: 1px solid var(--border);
  margin-top: 6px;
}
.total-label { font-size: 12px; color: var(--muted); text-transform: uppercase; letter-spacing: 1px; }
.total-monto { font-size: 22px; font-weight: 950; color: var(--primary2); }

/* Métodos pago */
.metodos-grid {
  display: grid;
  grid-template-columns: repeat(3, 1fr);
  gap: 8px;
}
.metodo-btn {
  background: rgba(255,255,255,.04);
  border: 1px solid var(--border);
  border-radius: 12px;
  color: var(--text);
  padding: 10px 6px;
  cursor: pointer;
  font-size: 12px;
  font-weight: 700;
  text-align: center;
  transition: border-color .12s, background .12s;
}
.metodo-btn:hover { border-color: var(--primary2); }
.metodo-btn.is-sel {
  border-color: var(--primary2);
  background: rgba(165,39,39,.15);
  color: var(--primary2);
}

/* Footer cobrar */
.pos__footer {
  padding: 12px 14px;
  border-top: 1px solid var(--border);
  background: rgba(0,0,0,.14);
}
.btn-cobrar {
  width: 100%;
  background: linear-gradient(180deg, var(--primary), rgba(135,25,25,1));
  border: none;
  border-radius: 16px;
  color: #fff;
  padding: 16px;
  font-size: 15px;
  font-weight: 950;
  letter-spacing: .4px;
  cursor: pointer;
  box-shadow: 0 10px 28px rgba(165,39,39,.40);
  transition: filter .12s, transform .1s;
}
.btn-cobrar:hover { filter: brightness(1.08); }
.btn-cobrar:active { transform: scale(.98); }
.btn-cobrar:disabled {
  background: rgba(255,255,255,.06);
  color: var(--muted);
  box-shadow: none;
  cursor: not-allowed;
}

/* Ticket modal override width */
.ticket-pre {
  font-family: 'Courier New', monospace;
  font-size: 12px;
  background: #fff;
  color: #111;
  border-radius: 12px;
  padding: 16px;
  line-height: 1.6;
  max-height: 45vh;
  overflow-y: auto;
}

/* Tabs estado */
.estado-tabs {
  display: flex;
  gap: 6px;
  margin-bottom: 12px;
  flex-wrap: wrap;
}
.estado-tab {
  border: 1px solid var(--border);
  background: rgba(255,255,255,.04);
  color: var(--muted);
  border-radius: 999px;
  padding: 7px 14px;
  font-size: 12px;
  font-weight: 700;
  cursor: pointer;
  transition: border-color .12s, color .12s;
}
.estado-tab:hover { border-color: var(--primary2); }
.estado-tab.is-sel {
  border-color: var(--primary2);
  background: rgba(165,39,39,.15);
  color: var(--primary2);
}

/* Badges estado en ordenes activas */
.e-pill {
  font-size: 11px;
  border-radius: 999px;
  padding: 4px 10px;
  border: 1px solid;
}
.e-pill.pendiente  { border-color: rgba(255,190,70,.4);  background: rgba(255,190,70,.1);  color: #ffbe46; }
.e-pill.en_proceso { border-color: rgba(60,140,255,.4);  background: rgba(60,140,255,.1);  color: #3c8cff; }
.e-pill.terminado  { border-color: rgba(60,180,120,.4);  background: rgba(60,180,120,.1);  color: #3cb478; }
.e-pill.entregado  { border-color: rgba(255,255,255,.2); background: rgba(255,255,255,.06); color: var(--muted); }

/* Ordenes activas bajo el POS */
.activas-wrap {
  margin-top: 16px;
}
.activas-grid {
  display: grid;
  grid-template-columns: repeat(auto-fill, minmax(220px, 1fr));
  gap: 10px;
}
.orden-card {
  border: 1px solid var(--border);
  border-radius: 18px;
  padding: 14px;
  background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  display: flex;
  flex-direction: column;
  gap: 8px;
}
.orden-card.pendiente  { border-color: rgba(255,190,70,.3); }
.orden-card.en_proceso { border-color: rgba(60,140,255,.3); box-shadow: 0 0 18px rgba(60,140,255,.1); }
.orden-card.terminado  { border-color: rgba(60,180,120,.3); }
.orden-card__head { display: flex; justify-content: space-between; align-items: center; }
.orden-card__id   { font-size: 11px; color: var(--muted); }
.orden-card__srv  { font-weight: 900; font-size: 14px; }
.orden-card__cli  { font-size: 12px; color: var(--muted); }
.orden-card__monto { font-size: 16px; font-weight: 950; color: var(--primary2); }
.orden-card__btns { display: flex; gap: 6px; flex-wrap: wrap; }
.orden-card__btns .btn-sm { font-size: 11px; padding: 6px 10px; }

@media (max-width: 900px) {
  .pos { grid-template-columns: 1fr; height: auto; }
  .pos__cobro { min-height: 500px; }
}

/* Estado de pago / extras en tarjetas de órdenes activas */
.pago-pill { display:inline-block; font-size:11px; font-weight:800; border-radius:999px; padding:4px 10px; margin-top:6px; }
.pago-pill.pagado { background:rgba(60,180,120,.15); color:#3cb478; border:1px solid rgba(60,180,120,.4); }
.pago-pill.sin-pagar { background:rgba(255,190,70,.15); color:#ffbe46; border:1px solid rgba(255,190,70,.4); }
.extra-pill { display:inline-block; font-size:11px; font-weight:800; border-radius:999px; padding:4px 10px; margin-top:6px; margin-left:6px; background:rgba(213,94,79,.15); color:#d55e4f; border:1px solid rgba(213,94,79,.4); cursor:pointer; }
.extras-wrap { display:none; margin-top:10px; border-top:1px dashed var(--border); padding-top:8px; }
.extras-wrap.open { display:block; }
.extra-review { background:rgba(0,0,0,.18); border:1px solid var(--border); border-radius:10px; padding:8px 10px; font-size:12px; margin-bottom:6px; }
.extra-review__just { color:var(--muted); margin-bottom:6px; }
.extra-review__row { display:flex; gap:6px; align-items:center; }
</style>

<div class="pos" id="posRoot">
  <!-- IZQUIERDA: grid de servicios -->
  <section class="pos__servicios">
    <div class="pos__label">Servicios de Autolavado</div>
    <div class="srv-grid" id="srvGrid">
      <div class="muted" style="padding:20px;grid-column:1/-1">Cargando servicios…</div>
    </div>

    <button class="extras-toggle" id="btnToggleExtras" type="button" style="display:none;">
      <span id="extrasToggleLabel">+ Agregar extra</span>
    </button>
    <div class="srv-grid" id="srvGridExtras" style="display:none; margin-top:10px;"></div>

    <!-- Cargo personalizado: aparte de "Pedir extra por dificultad" (esa
         requiere aprobación de admin). Este lo agrega caja/admin directo,
         con descripción libre y monto entre $50 y $2000. Un operario
         (Mecánico/Lavador) no maneja dinero, así que ni se le muestra. -->
    <?php if (!is_operario()): ?>
    <button class="extras-toggle" id="btnCargoPersonalizado" type="button" style="margin-top:8px;">
      + Cargo personalizado
    </button>
    <div class="cargo-form" id="cargoPersonalizadoForm" style="display:none"></div>
    <?php endif; ?>

    <!-- Órdenes activas de hoy -->
    <div class="activas-wrap">
      <div class="pos__label" style="margin-top:20px;margin-bottom:10px;display:flex;justify-content:space-between;align-items:center;gap:10px;flex-wrap:wrap">
        <span id="activasTitulo">Órdenes activas hoy</span>
        <div style="display:flex;gap:8px;align-items:center">
          <?php if (is_admin()): ?>
          <label style="display:flex;align-items:center;gap:6px;font-size:11px;font-weight:700;color:var(--muted);cursor:pointer;text-transform:none;letter-spacing:0">
            <input type="checkbox" id="chkTodasFechas"> Ver de todos los días
          </label>
          <?php endif; ?>
          <button class="btn-sm" id="btnRefreshActivas" type="button">↻ Refrescar</button>
        </div>
      </div>
      <div class="activas-grid" id="activasGrid">
        <div class="muted" style="font-size:13px">Cargando…</div>
      </div>
    </div>
  </section>

  <!-- DERECHA: panel cobro -->
  <aside class="pos__cobro">
    <div class="pos__cobro-scroll">

      <!-- Cliente -->
      <div class="pos__label">Cliente <span style="color:var(--muted);font-weight:400">(opcional)</span></div>
      <div class="autocomplete-wrap">
        <input
          class="select"
          id="autoCliBuscar"
          type="search"
          placeholder="Buscar nombre o teléfono…"
          autocomplete="off"
          style="width:100%;border-radius:14px;padding:10px 12px;"
        >
        <div class="autocomplete-drop" id="autoCliDrop"></div>
      </div>
      <div class="cliente-chip" id="clienteChip">
        <div class="cliente-chip__info">
          <div class="cliente-chip__name" id="chipNombre">—</div>
          <div class="cliente-chip__tel"  id="chipTel"></div>
        </div>
        <button class="cliente-chip__clear" id="btnCliClear" type="button">✕</button>
      </div>
      <button class="btn-dashed" id="btnNuevoCliente" type="button">+ Nuevo cliente</button>

      <!-- Vehículo (se muestra sólo si hay cliente) -->
      <div id="secVehiculo" style="display:none;margin-top:14px">
        <div class="pos__label">Vehículo</div>
        <div class="vh-list" id="vhList"></div>
        <button class="btn-dashed" id="btnNuevoVehiculo" type="button">+ Agregar vehículo</button>
      </div>

      <div class="hr" style="margin:14px 0"></div>

      <!-- Orden -->
      <div class="pos__label">Orden</div>
      <div class="orden-lista" id="ordenLista">
        <div class="orden-empty">Selecciona servicios a la izquierda</div>
      </div>
      <div class="total-row">
        <span class="total-label">Total</span>
        <span class="total-monto" id="totalMonto">$0.00</span>
      </div>

      <div class="hr" style="margin:14px 0"></div>

      <!-- Método de pago -->
      <div class="pos__label">Método de pago</div>
      <div class="metodos-grid">
        <button class="metodo-btn is-sel" data-pago="efectivo"       type="button">💵 Efectivo</button>
        <button class="metodo-btn"        data-pago="tarjeta"        type="button">💳 Tarjeta</button>
        <button class="metodo-btn"        data-pago="transferencia"  type="button">📲 Transfer</button>
      </div>

      <div class="hr" style="margin:14px 0"></div>

      <!-- Notas -->
      <div class="pos__label">Notas</div>
      <textarea
        class="select"
        id="autoNotas"
        rows="2"
        placeholder="Observaciones del servicio…"
        style="width:100%;resize:none;border-radius:14px;padding:10px 12px;"
      ></textarea>

    </div><!-- /scroll -->

    <!-- Botón cobrar -->
    <div class="pos__footer">
      <button class="btn-cobrar" id="btnCobrar" disabled type="button">COBRAR</button>
    </div>
  </aside>
</div><!-- /pos -->

<!-- ══════════════════════════════════════════
     MODALES
══════════════════════════════════════════ -->

<!-- Modal: Nuevo cliente -->
<div class="modal" id="modalNuevoCli">
  <div class="modal__backdrop" data-modal-close="modalNuevoCli"></div>
  <div class="modal__dialog" style="max-width:420px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Nuevo cliente</h3>
        <p class="modal__subtitle">Se guarda en la base de datos.</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalNuevoCli">✕</button>
    </div>
    <div class="modal__body">
      <div class="field" style="margin-bottom:12px">
        <label>Nombre completo *</label>
        <input class="select" id="ncNombre" type="text" placeholder="Nombre Apellido" style="width:100%;padding:10px 12px;border-radius:14px">
      </div>
      <div class="field">
        <label>Teléfono *</label>
        <input class="select" id="ncTel" type="tel" placeholder="3411234567" style="width:100%;padding:10px 12px;border-radius:14px">
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalNuevoCli">Cancelar</button>
      <button class="btn" id="btnGuardarCliente" type="button">Guardar cliente</button>
    </div>
  </div>
</div>

<!-- Modal: Nuevo vehículo -->
<div class="modal" id="modalNuevoVeh">
  <div class="modal__backdrop" data-modal-close="modalNuevoVeh"></div>
  <div class="modal__dialog" style="max-width:480px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Nuevo vehículo</h3>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalNuevoVeh">✕</button>
    </div>
    <div class="modal__body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field" style="grid-column:1/-1">
          <label>Marca *</label>
          <input class="select" id="vhMarca" type="text" placeholder="Honda, Yamaha, Italika…" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Modelo</label>
          <input class="select" id="vhModelo" type="text" placeholder="CBR, FZ…" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Año</label>
          <input class="select" id="vhAnio" type="number" placeholder="2023" min="1990" max="2035" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Color</label>
          <input class="select" id="vhColor" type="text" placeholder="Rojo" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Placa</label>
          <input class="select" id="vhPlaca" type="text" placeholder="ABC-123" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalNuevoVeh">Cancelar</button>
      <button class="btn" id="btnGuardarVehiculo" type="button">Guardar vehículo</button>
    </div>
  </div>
</div>

<!-- Modal: Ticket -->
<div class="modal" id="modalTicketAuto">
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" style="max-width:400px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Venta registrada ✓</h3>
        <p class="modal__subtitle" id="ticketFolio"></p>
      </div>
    </div>
    <div class="modal__body">
      <div class="ticket-pre" id="ticketContenido"></div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" id="btnImprimirTicket">Imprimir</button>
      <button class="btn" type="button" id="btnNuevaOrden">Nueva orden</button>
    </div>
  </div>
</div>

<!-- Modal: Cobrar orden activa -->
<div class="modal" id="modalCobrarActiva">
  <div class="modal__backdrop" data-modal-close="modalCobrarActiva"></div>
  <div class="modal__dialog" style="max-width:400px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Cobrar orden</h3>
        <p class="modal__subtitle" id="cobrarActivaInfo"></p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalCobrarActiva">✕</button>
    </div>
    <div class="modal__body">
      <div class="pos__label">Método de pago</div>
      <div class="metodos-grid">
        <button class="metodo-btn is-sel" data-pago-activa="efectivo"      type="button">💵 Efectivo</button>
        <button class="metodo-btn"        data-pago-activa="tarjeta"       type="button">💳 Tarjeta</button>
        <button class="metodo-btn"        data-pago-activa="transferencia" type="button">📲 Transfer</button>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalCobrarActiva">Cancelar</button>
      <button class="btn" id="btnConfirmarCobro" type="button">Cobrar</button>
    </div>
  </div>
</div>

<script>
// ════════════════════════════════════════════════════
//  AUTOLAVADO POS  — autolavado.php inline script
// ════════════════════════════════════════════════════
(() => {
  const API = 'php/api';
  // Solo administración puede borrar manualmente una orden (limpieza de
  // pedidos duplicados por reintentos de red desde la app móvil).
  const ES_ADMIN = <?= json_encode(is_admin()) ?>;
  // El cargo personalizado lo agrega directo quien maneja dinero (caja/admin),
  // sin pasar por aprobación — un operario (Mecánico/Lavador) no debe verlo.
  const ES_OPERARIO = <?= json_encode(is_operario()) ?>;

  const state = {
    servicios:   [],
    carrito:     [],           // [{id, nombre, precio}]
    cliente:     null,         // {id, nombre_completo, telefono}
    vehiculo:    null,
    metodoPago:  'efectivo',
    metodoActiva:'efectivo',
    ordenActivaId: 0,
    cliTimer:    null,
    grupoExpandido: null,      // nombre del grupo (paquete) con el sub-selector de tamaño abierto
    extrasAbierto: false,
  };

  // Paquetes principales: se muestran como botón grande con sub-selector de
  // tamaño. Todo lo demás (Lavado Interior, Full Detallado Interior, y los
  // servicios sueltos) se agrupa bajo "+ Agregar extra" para no saturar la
  // pantalla — antes eran ~26 botones a la vista al mismo tiempo.
  const PRINCIPAL_GROUPS = ['Detallado Spa', 'Detallado Braw', 'Detallado Luxury'];
  // Servicios sueltos (sin variantes de tamaño) que también se muestran
  // como botón principal en vez de esconderse en "+ Agregar extra".
  const PRINCIPAL_SUELTOS = ['Lavado de Moto', 'Detallado de Moto', 'Detallado Premium de Moto'];

  // Agrupa servicios cuyo nombre sigue el patrón "Grupo - Talla" (ej.
  // "Detallado Spa - Auto"); el resto queda como extra suelto.
  function agruparServicios(lista) {
    const grupos = {}; // nombre grupo -> [{talla, servicio}]
    const sueltos = [];
    lista.forEach(s => {
      // El "Cargo personalizado" no es un botón del catálogo — tiene su
      // propio botón "+ Cargo personalizado" con descripción y monto libres.
      if (s.es_personalizado) return;
      const idx = s.nombre.indexOf(' - ');
      if (idx > -1) {
        const grupo = s.nombre.slice(0, idx);
        const talla = s.nombre.slice(idx + 3);
        (grupos[grupo] = grupos[grupo] || []).push({ talla, servicio: s });
      } else {
        sueltos.push(s);
      }
    });
    return { grupos, sueltos };
  }

  // ── Helpers ──────────────────────────────────────
  const $  = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  function setHTML(id, html) { const el=$(id); if(el) el.innerHTML = html; }
  function setText(id, t)    { const el=$(id); if(el) el.textContent = t;  }

  // ── Cargar servicios ─────────────────────────────
  async function cargarServicios() {
    try {
      const r = await fetch(`${API}/servicios/list.php?tipo=autolavado`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      state.servicios = j.data || [];
      renderServicios();
    } catch(e) {
      setHTML('srvGrid', `<div class="muted" style="grid-column:1/-1;padding:20px">Error: ${esc(e.message)}</div>`);
    }
  }

  function grupoCardHTML(grupo, tallas) {
    const expandido = state.grupoExpandido === grupo;
    const enCarrito = tallas.some(t => state.carrito.some(c => c.id === t.servicio.id));

    if (expandido) {
      return `
        <div class="srv-btn is-sel" data-grupo="${esc(grupo)}">
          <div class="srv-btn__name">${esc(grupo)}</div>
          <div class="srv-btn__hint">Elige el tamaño:</div>
          <div class="srv-btn__tallas">
            ${tallas.map(t => `
              <button class="talla-btn" data-sid="${t.servicio.id}" type="button">
                ${esc(t.talla)} <span class="talla-btn__precio">$${Number(t.servicio.precio).toFixed(0)}</span>
              </button>`).join('')}
          </div>
        </div>`;
    }

    return `
      <button class="srv-btn ${enCarrito ? 'is-sel' : ''}" data-grupoabrir="${esc(grupo)}" type="button">
        <div class="srv-btn__badge">${enCarrito ? 1 : ''}</div>
        <div class="srv-btn__name">${esc(grupo)}</div>
        <div class="srv-btn__hint">Toca para elegir tamaño</div>
      </button>`;
  }

  function sueltoCardHTML(s) {
    const enCarrito = state.carrito.find(c => c.id === s.id);
    return `
      <button class="srv-btn ${enCarrito ? 'is-sel' : ''}" data-sid="${s.id}" type="button">
        <div class="srv-btn__badge">${enCarrito ? 1 : ''}</div>
        <div class="srv-btn__name">${esc(s.nombre)}</div>
        ${s.descripcion ? `<div class="srv-btn__desc">${esc(s.descripcion)}</div>` : ''}
        <div class="srv-btn__precio">$${Number(s.precio).toFixed(2)}</div>
        ${s.tiempo_estimado ? `<div class="srv-btn__tiempo">~${s.tiempo_estimado} min</div>` : ''}
      </button>`;
  }

  function renderServicios() {
    const grid = $('srvGrid');
    const gridExtras = $('srvGridExtras');
    const btnToggle = $('btnToggleExtras');
    if (!grid) return;

    if (!state.servicios.length) {
      grid.innerHTML = '<div class="muted" style="grid-column:1/-1;padding:20px">Sin servicios activos.</div>';
      if (btnToggle) btnToggle.style.display = 'none';
      return;
    }

    const { grupos, sueltos } = agruparServicios(state.servicios);
    const sueltosPrincipales = sueltos.filter(s => PRINCIPAL_SUELTOS.includes(s.nombre));
    const sueltosExtra = sueltos.filter(s => !PRINCIPAL_SUELTOS.includes(s.nombre));

    // Principales: los 3 paquetes (en el orden fijo de arriba) más los
    // sueltos promovidos a principal (ej. Lavado de Motor).
    grid.innerHTML = PRINCIPAL_GROUPS
      .filter(g => grupos[g])
      .map(g => grupoCardHTML(g, grupos[g]))
      .join('') + sueltosPrincipales.map(sueltoCardHTML).join('');

    // Extras: el resto de los grupos por tamaño (Lavado Interior, Full
    // Detallado Interior, etc.) más los demás servicios sueltos.
    const extraGrupos = Object.keys(grupos).filter(g => !PRINCIPAL_GROUPS.includes(g));
    const extrasHTML = extraGrupos.map(g => grupoCardHTML(g, grupos[g])).join('')
      + sueltosExtra.map(sueltoCardHTML).join('');

    if (gridExtras) gridExtras.innerHTML = extrasHTML;

    if (btnToggle) {
      btnToggle.style.display = extrasHTML ? '' : 'none';
    }
    if (gridExtras) gridExtras.style.display = state.extrasAbierto ? '' : 'none';
    const lbl = $('extrasToggleLabel');
    if (lbl) lbl.textContent = state.extrasAbierto ? '– Ocultar extras' : '+ Agregar extra';

    // Delegación de clicks: abrir un grupo (mostrar tamaños)
    [grid, gridExtras].forEach(g => {
      if (!g) return;
      g.querySelectorAll('[data-grupoabrir]').forEach(btn => {
        btn.addEventListener('click', () => {
          state.grupoExpandido = btn.dataset.grupoabrir;
          renderServicios();
        });
      });
      // Elegir un tamaño o un servicio suelto: agrega/quita del carrito.
      g.querySelectorAll('[data-sid]').forEach(btn => {
        btn.addEventListener('click', () => {
          const id = Number(btn.dataset.sid);
          const srv = state.servicios.find(s => s.id === id);
          if (!srv) return;
          const idx = state.carrito.findIndex(c => c.id === id);
          if (idx >= 0) state.carrito.splice(idx, 1);
          else state.carrito.push({ id: srv.id, nombre: srv.nombre, precio: parseFloat(srv.precio) });
          state.grupoExpandido = null;
          renderServicios();
          renderOrden();
        });
      });
    });
  }

  $('btnToggleExtras')?.addEventListener('click', () => {
    state.extrasAbierto = !state.extrasAbierto;
    renderServicios();
  });

  // ── Cargo personalizado ───────────────────────────
  // Aparte de "Pedir extra por dificultad" (esa requiere aprobación de
  // admin sobre una orden ya activa). Este se agrega directo al armar el
  // pedido, con descripción libre y un monto entre $50 y $2000 — lo agrega
  // caja/admin, así que no necesita pasar por revisión.
  function servicioPersonalizado() {
    return state.servicios.find(s => s.es_personalizado);
  }

  $('btnCargoPersonalizado')?.addEventListener('click', () => {
    const wrap = $('cargoPersonalizadoForm');
    if (!wrap) return;
    const abrir = wrap.style.display === 'none';
    if (!abrir) { wrap.style.display = 'none'; wrap.innerHTML = ''; return; }

    wrap.style.display = 'block';
    wrap.innerHTML = `
      <div class="cargo-form__label">¿Qué se está cobrando?</div>
      <input class="cargo-form__desc" id="cargoDesc" type="text" placeholder="Ej. trajo casco extra, manchas de pintura…" maxlength="150">
      <div class="cargo-form__monto-wrap">
        <span>$</span>
        <input class="cargo-form__monto" id="cargoMonto" type="number" min="50" max="2000" step="10" placeholder="Monto">
      </div>
      <div class="cargo-form__hint">Entre $50 y $2000.</div>
      <div class="cargo-form__btns">
        <button class="btn" type="button" id="btnAgregarCargo">Agregar al pedido</button>
        <button class="btn btn--ghost" type="button" id="btnCancelarCargo">Cancelar</button>
      </div>
    `;

    $('btnCancelarCargo').addEventListener('click', () => {
      wrap.style.display = 'none';
      wrap.innerHTML = '';
    });

    $('btnAgregarCargo').addEventListener('click', () => {
      const srv = servicioPersonalizado();
      if (!srv) { toast('Error', 'No se encontró el servicio de cargo personalizado.'); return; }
      const desc = $('cargoDesc').value.trim();
      const monto = Number($('cargoMonto').value);
      if (!desc) { toast('Falta descripción', 'Escribe qué se está cobrando.'); return; }
      if (!monto || monto < 50 || monto > 2000) { toast('Monto inválido', 'Debe estar entre $50 y $2000.'); return; }
      state.carrito.push({ id: srv.id, nombre: `Cargo: ${desc}`, precio: monto, _notas: desc });
      wrap.style.display = 'none';
      wrap.innerHTML = '';
      renderOrden();
      toast('Cargo agregado', `${desc} · $${monto.toFixed(0)}`);
    });
  });

  // ── Render orden ──────────────────────────────────
  function renderOrden() {
    const lista = $('ordenLista');
    const cobrar = $('btnCobrar');
    if (!lista) return;

    if (!state.carrito.length) {
      lista.innerHTML = '<div class="orden-empty">Selecciona servicios a la izquierda</div>';
      setText('totalMonto', '$0.00');
      if (cobrar) cobrar.disabled = true;
      return;
    }

    let total = 0;
    lista.innerHTML = state.carrito.map((it, i) => {
      total += it.precio;
      return `
        <div class="orden-item">
          <span class="orden-item__name">${esc(it.nombre)}</span>
          <span class="orden-item__precio-wrap">$<input class="orden-item__precio-inp" type="number" min="0" step="0.01" value="${it.precio.toFixed(2)}" data-precioidx="${i}"></span>
          <button class="orden-item__rm" data-rm="${i}" type="button">✕</button>
        </div>`;
    }).join('');

    lista.querySelectorAll('[data-rm]').forEach(btn => {
      btn.addEventListener('click', () => {
        state.carrito.splice(Number(btn.dataset.rm), 1);
        renderServicios();
        renderOrden();
      });
    });

    // Precio editable por línea: útil para servicios "desde $X" o a cotizar
    // según el vehículo. No toca renderServicios()/renderOrden() completo
    // para no perder el foco del input mientras se escribe.
    lista.querySelectorAll('[data-precioidx]').forEach(inp => {
      inp.addEventListener('input', () => {
        const idx = Number(inp.dataset.precioidx);
        const val = Math.max(0, Number(inp.value) || 0);
        if (state.carrito[idx]) state.carrito[idx].precio = val;
        let t = 0;
        state.carrito.forEach(it => t += it.precio);
        setText('totalMonto', '$' + t.toFixed(2));
      });
    });

    setText('totalMonto', '$' + total.toFixed(2));
    if (cobrar) cobrar.disabled = false;
  }

  // ── Búsqueda cliente ──────────────────────────────
  const inpCli  = $('autoCliBuscar');
  const dropCli = $('autoCliDrop');

  inpCli?.addEventListener('input', () => {
    clearTimeout(state.cliTimer);
    const q = inpCli.value.trim();
    if (q.length < 2) { dropCli.classList.remove('open'); return; }
    state.cliTimer = setTimeout(() => buscarClientes(q), 280);
  });

  async function buscarClientes(q) {
    try {
      // Ruta correcta relativa al dashboard
      const r = await fetch(`php/api/clientes/list.php?q=${encodeURIComponent(q)}`);
      const j = await r.json();
      const list = Array.isArray(j) ? j : (j.data || []);
      dropCli.innerHTML = list.length
        ? list.map(c => `<div class="ac-item" data-cid="${c.id}" data-nom="${esc(c.nombre_completo)}" data-tel="${esc(c.telefono)}"><strong>${esc(c.nombre_completo)}</strong> <small>${esc(c.telefono)}</small></div>`).join('')
        : '<div class="ac-item" style="color:var(--muted)">Sin resultados</div>';
      dropCli.classList.add('open');

      dropCli.querySelectorAll('[data-cid]').forEach(item => {
        item.addEventListener('click', () => seleccionarCliente({
          id: Number(item.dataset.cid),
          nombre_completo: item.dataset.nom,
          telefono: item.dataset.tel,
        }));
      });
    } catch(e) {}
  }

  function seleccionarCliente(c) {
    state.cliente  = c;
    state.vehiculo = null;
    dropCli.classList.remove('open');
    if (inpCli) inpCli.value = '';
    $('clienteChip')?.classList.add('show');
    setText('chipNombre', c.nombre_completo);
    setText('chipTel', c.telefono);
    const secV = $('secVehiculo');
    if (secV) secV.style.display = 'block';
    cargarVehiculos(c.id);
  }

  $('btnCliClear')?.addEventListener('click', () => {
    state.cliente  = null;
    state.vehiculo = null;
    $('clienteChip')?.classList.remove('show');
    const secV = $('secVehiculo');
    if (secV) secV.style.display = 'none';
    setHTML('vhList', '');
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('#autoCliBuscar') && !e.target.closest('#autoCliDrop')) {
      dropCli?.classList.remove('open');
    }
  });

  // ── Nuevo cliente ─────────────────────────────────
  $('btnNuevoCliente')?.addEventListener('click', () => {
    $('ncNombre').value = '';
    $('ncTel').value    = '';
    modalOpen('modalNuevoCli');
    setTimeout(() => $('ncNombre')?.focus(), 60);
  });

  $('btnGuardarCliente')?.addEventListener('click', async () => {
    const nombre = $('ncNombre')?.value.trim() || '';
    const tel    = $('ncTel')?.value.trim()    || '';
    if (!nombre || !tel) { toast('Faltan datos', 'Nombre y teléfono son requeridos.', {icon:'⚠️'}); return; }
    try {
      const r = await fetch('php/api/clientes/create.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({nombre_completo: nombre, telefono: tel}),
      });
      const j = await r.json();
      if (j.error) throw new Error(j.error);
      modalClose('modalNuevoCli');
      seleccionarCliente(j);
      toast('Cliente registrado', nombre, {icon:'👤'});
    } catch(e) { toast('Error', e.message, {icon:'⚠️'}); }
  });

  // ── Vehículos ─────────────────────────────────────
  async function cargarVehiculos(clienteId) {
    const lista = $('vhList');
    if (!lista) return;
    lista.innerHTML = '<span style="color:var(--muted);font-size:12px">Cargando…</span>';
    try {
      const r = await fetch(`php/api/vehiculos/list.php?cliente_id=${clienteId}`);
      const j = await r.json();
      const vhs = Array.isArray(j) ? j : (j.data || []);
      lista.innerHTML = vhs.map(v => `
        <button class="vh-btn" data-vid="${v.id}" type="button">
          <div class="vh-btn__label">${esc(v.marca)} ${esc(v.modelo||'')} ${v.año||v.anio||''}</div>
          <div class="vh-btn__placa">${esc(v.color||'')}${v.placa?' • '+esc(v.placa):''}</div>
        </button>`).join('');

      lista.querySelectorAll('.vh-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          lista.querySelectorAll('.vh-btn').forEach(b => b.classList.remove('is-sel'));
          btn.classList.add('is-sel');
          state.vehiculo = { id: Number(btn.dataset.vid) };
        });
      });
    } catch(e) {
      lista.innerHTML = '<span style="color:var(--muted);font-size:12px">Error al cargar.</span>';
    }
  }

  $('btnNuevoVehiculo')?.addEventListener('click', () => {
    ['vhMarca','vhModelo','vhAnio','vhColor','vhPlaca'].forEach(id => { const el=$(id); if(el) el.value=''; });
    modalOpen('modalNuevoVeh');
    setTimeout(() => $('vhMarca')?.focus(), 60);
  });

  $('btnGuardarVehiculo')?.addEventListener('click', async () => {
    if (!state.cliente) return;
    const marca = $('vhMarca')?.value.trim() || '';
    if (!marca) { toast('Falta marca', '', {icon:'⚠️'}); return; }
    try {
      const r = await fetch('php/api/vehiculos/create.php', {
        method: 'POST',
        headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          cliente_id: state.cliente.id,
          marca, modelo: $('vhModelo')?.value||'',
          año: $('vhAnio')?.value||null,
          color: $('vhColor')?.value||'', placa: $('vhPlaca')?.value||'',
        }),
      });
      const j = await r.json();
      if (j.error) throw new Error(j.error);
      modalClose('modalNuevoVeh');
      cargarVehiculos(state.cliente.id);
      toast('Vehículo registrado', marca, {icon:'🏍️'});
    } catch(e) { toast('Error', e.message, {icon:'⚠️'}); }
  });

  // ── Métodos de pago ───────────────────────────────
  document.querySelectorAll('[data-pago]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-pago]').forEach(b => b.classList.remove('is-sel'));
      btn.classList.add('is-sel');
      state.metodoPago = btn.dataset.pago;
    });
  });

  document.querySelectorAll('[data-pago-activa]').forEach(btn => {
    btn.addEventListener('click', () => {
      document.querySelectorAll('[data-pago-activa]').forEach(b => b.classList.remove('is-sel'));
      btn.classList.add('is-sel');
      state.metodoActiva = btn.dataset.pagoActiva;
    });
  });

  // ── COBRAR (nueva orden desde POS) ───────────────
  $('btnCobrar')?.addEventListener('click', async () => {
    if (!state.carrito.length) return;
    const btn = $('btnCobrar');
    btn.disabled = true;
    btn.textContent = 'Procesando…';

    try {
      // 1. Crear órdenes en autolavado_ordenes
      const ids = [];
      for (const srv of state.carrito) {
        const r = await fetch(`${API}/autolavado/create.php`, {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({
            servicio_id: srv.id,
            cliente_id:  state.cliente?.id  || null,
            vehiculo_id: state.vehiculo?.id || null,
            notas: srv._notas || $('autoNotas')?.value || '',
            precio: srv.precio,
          }),
        });
        const j = await r.json();
        if (!j.ok) throw new Error(j.error || 'Error al crear orden');
        ids.push(j.id);
      }

      // 2. Cobrar cada orden generada (se cobra ANTES de iniciar el
      // trabajo; la orden queda pendiente y pagada, lista para que el
      // lavador la tome desde la app móvil).
      let lastVentaId = 0, lastFolio = '';
      for (const ordId of ids) {
        const rc = await fetch(`${API}/autolavado/cobrar.php`, {
          method: 'POST',
          headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ id: ordId, metodo_pago: state.metodoPago }),
        });
        const jc = await rc.json();
        if (!jc.ok) throw new Error(jc.error || 'Error al cobrar');
        lastVentaId = jc.venta_id;
        lastFolio   = jc.folio;
      }

      mostrarTicket(lastVentaId, lastFolio);
      cargarActivas();
      btn.textContent = 'COBRAR';

    } catch(e) {
      toast('Error', e.message, {icon:'⚠️', ttl:4000});
      btn.disabled = false;
      btn.textContent = 'COBRAR';
    }
  });

  // ── Ticket ────────────────────────────────────────
  function mostrarTicket(ventaId, folio) {
    const total = state.carrito.reduce((s, i) => s + i.precio, 0);
    const items = state.carrito.map(i => `${i.nombre.padEnd(20).slice(0,20)}  $${i.precio.toFixed(2)}`).join('\n');
    const metodoLabel = {efectivo:'Efectivo', tarjeta:'Tarjeta', transferencia:'Transferencia'};
    const ahora = new Date().toLocaleString('es-MX');

    setText('ticketFolio', `Folio: ${folio}`);
    $('ticketContenido').innerHTML = `<pre style="margin:0;font-family:monospace;font-size:12px;color:#111">
BRAW MOTORS — AUTOLAVADO
${ahora}
——————————————————————————
Cliente: ${state.cliente?.nombre_completo || 'Mostrador'}
${$('autoNotas')?.value ? 'Notas: '+$('autoNotas').value : ''}
——————————————————————————
${items}
——————————————————————————
TOTAL     $${total.toFixed(2)}
Pago:     ${metodoLabel[state.metodoPago] || state.metodoPago}
——————————————————————————
¡Gracias por su preferencia!
    </pre>`;

    $('btnImprimirTicket')._vid = ventaId;
    modalOpen('modalTicketAuto');
  }

  $('btnImprimirTicket')?.addEventListener('click', () => {
    const vid = $('btnImprimirTicket')._vid;
    if (vid) window.open(`ticket.php?id=${vid}`, '_blank');
  });

  $('btnNuevaOrden')?.addEventListener('click', () => {
    state.carrito  = [];
    state.cliente  = null;
    state.vehiculo = null;
    $('clienteChip')?.classList.remove('show');
    const secV = $('secVehiculo');
    if (secV) secV.style.display = 'none';
    if ($('autoNotas')) $('autoNotas').value = '';
    renderServicios();
    renderOrden();
    const btnC = $('btnCobrar');
    if (btnC) btnC.textContent = 'COBRAR';
    modalClose('modalTicketAuto');
    toast('Listo', 'Nueva orden lista', {icon:'✓'});
  });

  // ── Órdenes activas ───────────────────────────────
  async function cargarActivas() {
    const grid = $('activasGrid');
    if (!grid) return;
    grid.innerHTML = '<div class="muted" style="font-size:13px">Cargando…</div>';
    try {
      const todas = ES_ADMIN && $('chkTodasFechas')?.checked;
      const url = todas ? `${API}/autolavado/list.php?todas_fechas=1` : `${API}/autolavado/list.php`;
      const titulo = $('activasTitulo');
      if (titulo) titulo.textContent = todas ? 'Órdenes activas (todos los días)' : 'Órdenes activas hoy';

      const r = await fetch(url);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);

      const activas = (j.data || []).filter(o => o.estado !== 'entregado');
      if (!activas.length) {
        grid.innerHTML = `<div class="muted" style="font-size:13px;padding:10px 0">Sin órdenes activas${todas ? '' : ' hoy'}.</div>`;
        return;
      }

      grid.innerHTML = activas.map(o => {
        const abierto = !!state.extrasAbiertos?.[o.id];
        return `
        <div class="orden-card ${o.estado}">
          <div class="orden-card__head">
            <span class="orden-card__id">#${o.id}</span>
            <span class="e-pill ${o.estado}">${o.estado.replace('_',' ')}</span>
          </div>
          <div class="orden-card__srv">${esc(o.servicio)}</div>
          <div class="orden-card__cli">${o.cliente ? esc(o.cliente) : 'Sin cliente'}${o.placa ? ' • '+esc(o.placa) : ''}</div>
          <div class="orden-card__monto">$${Number(o.precio).toFixed(2)}</div>
          <div class="pago-pill ${o.pagado ? 'pagado' : 'sin-pagar'}">${o.pagado ? '✓ Pagado' : '⏳ Sin pagar'}</div>
          ${o.extras_pendientes > 0 ? `<button class="extra-pill" data-toggleextras="${o.id}" type="button">⚡ ${o.extras_pendientes} extra${o.extras_pendientes>1?'s':''} por revisar</button>` : ''}
          <div class="orden-card__btns">
            ${!o.pagado ? `<button class="btn-sm" data-accion="cobrar" data-oid="${o.id}" data-srv="${esc(o.servicio)}" data-monto="${o.precio}" type="button">💰 Cobrar</button>` : ''}
            ${o.estado === 'pendiente'  ? `<button class="btn-sm" data-accion="en_proceso" data-oid="${o.id}" type="button">▶ Iniciar</button>` : ''}
            ${o.estado === 'en_proceso' ? `<button class="btn-sm" data-accion="terminado"  data-oid="${o.id}" type="button">✓ Terminar</button>` : ''}
            ${o.estado === 'terminado'  ? `<button class="btn-sm" data-accion="entregado"  data-oid="${o.id}" type="button">📦 Entregar</button>` : ''}
            ${ES_ADMIN ? `<button class="btn-sm" data-eliminar="${o.id}" data-srv="${esc(o.servicio)}" data-pagado="${o.pagado ? 1 : 0}" type="button" style="color:#ff5a5a">🗑 Eliminar</button>` : ''}
          </div>
          <div class="extras-wrap ${abierto ? 'open' : ''}" id="extras-${o.id}"></div>
        </div>`;
      }).join('');

      // Eventos de acciones
      grid.querySelectorAll('[data-accion]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const accion = btn.dataset.accion;
          const oid    = Number(btn.dataset.oid);

          if (accion === 'cobrar') {
            state.ordenActivaId = oid;
            state.metodoActiva  = 'efectivo';
            document.querySelectorAll('[data-pago-activa]').forEach(b => {
              b.classList.toggle('is-sel', b.dataset.pagoActiva === 'efectivo');
            });
            setText('cobrarActivaInfo', `${btn.dataset.srv} — $${Number(btn.dataset.monto).toFixed(2)}`);
            modalOpen('modalCobrarActiva');
            return;
          }

          // Cambiar estado
          try {
            const r = await fetch(`${API}/autolavado/update_estado.php`, {
              method: 'POST', headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ id: oid, estado: accion }),
            });
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);
            cargarActivas();
          } catch(e) { toast('Error', e.message, {icon:'⚠️'}); }
        });
      });

      grid.querySelectorAll('[data-eliminar]').forEach(btn => {
        btn.addEventListener('click', async () => {
          const oid = Number(btn.dataset.eliminar);
          const pagado = btn.dataset.pagado === '1';
          const advertencia = pagado
            ? `La orden "${btn.dataset.srv}" ya fue cobrada. Se borrará el registro operativo, pero la venta se queda intacta en Ventas/Corte. ¿Borrar de todos modos?`
            : `¿Borrar la orden "${btn.dataset.srv}"? Esto es para limpiar pedidos duplicados por fallas de red — no se puede deshacer.`;
          if (!window.confirm(advertencia)) return;
          try {
            const r = await fetch(`${API}/autolavado/delete.php`, {
              method: 'POST', headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ id: oid }),
            });
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);
            toast('Orden eliminada', '', {icon:'🗑'});
            cargarActivas();
          } catch(e) { toast('Error', e.message, {icon:'⚠️'}); }
        });
      });

      grid.querySelectorAll('[data-toggleextras]').forEach(btn => {
        btn.addEventListener('click', () => {
          const oid = Number(btn.dataset.toggleextras);
          state.extrasAbiertos = state.extrasAbiertos || {};
          state.extrasAbiertos[oid] = !state.extrasAbiertos[oid];
          if (state.extrasAbiertos[oid]) cargarExtrasAutolavado(oid);
          else cargarActivas();
        });
      });

    } catch(e) {
      grid.innerHTML = `<div class="muted" style="font-size:13px">Error: ${esc(e.message)}</div>`;
    }
  }

  // ── Extras (recargo por dificultad) — aprobar/rechazar ────────────
  async function cargarExtrasAutolavado(ordenId) {
    const wrap = $('extras-' + ordenId);
    if (!wrap) return;
    wrap.classList.add('open');
    wrap.innerHTML = '<div class="muted" style="font-size:12px;padding:8px 0">Cargando…</div>';
    try {
      const r = await fetch(`${API}/autolavado/extras/listar.php?orden_id=${ordenId}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      const pendientes = (j.data || []).filter(ex => ex.estado === 'pendiente');
      if (!pendientes.length) {
        wrap.innerHTML = '<div class="muted" style="font-size:12px;padding:8px 0">Sin extras pendientes.</div>';
        return;
      }
      wrap.innerHTML = pendientes.map(ex => `
        <div class="extra-review" data-exid="${ex.id}">
          <div class="extra-review__just">${esc(ex.justificacion)}</div>
          <div class="extra-review__row">
            <input class="select" type="number" min="0" step="0.01" value="${Number(ex.monto_sugerido).toFixed(2)}" id="montoFinal-${ex.id}" style="width:100px;padding:6px 8px;border-radius:10px;font-size:12px">
            <button class="btn-sm" data-aprobar="${ex.id}" data-oid="${ordenId}" type="button">✓ Aprobar</button>
            <button class="btn-sm" data-rechazar="${ex.id}" data-oid="${ordenId}" type="button" style="color:#ff5a5a">✕ Rechazar</button>
          </div>
        </div>`).join('');

      wrap.querySelectorAll('[data-aprobar]').forEach(b => {
        b.addEventListener('click', async () => {
          const exId = Number(b.dataset.aprobar);
          const oid = Number(b.dataset.oid);
          const monto = Number($('montoFinal-' + exId).value || 0);
          try {
            const rr = await fetch(`${API}/autolavado/extras/revisar.php`, {
              method: 'POST', headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ id: exId, accion: 'aprobar', monto_final: monto }),
            });
            const jr = await rr.json();
            if (!jr.ok) throw new Error(jr.error);
            toast('Extra aprobado', '', {icon:'✓'});
            cargarExtrasAutolavado(oid);
            cargarActivas();
          } catch(e) { toast('Error', e.message, {icon:'⚠️'}); }
        });
      });
      wrap.querySelectorAll('[data-rechazar]').forEach(b => {
        b.addEventListener('click', async () => {
          const exId = Number(b.dataset.rechazar);
          const oid = Number(b.dataset.oid);
          try {
            const rr = await fetch(`${API}/autolavado/extras/revisar.php`, {
              method: 'POST', headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ id: exId, accion: 'rechazar' }),
            });
            const jr = await rr.json();
            if (!jr.ok) throw new Error(jr.error);
            toast('Extra rechazado', '', {icon:'✓'});
            cargarExtrasAutolavado(oid);
            cargarActivas();
          } catch(e) { toast('Error', e.message, {icon:'⚠️'}); }
        });
      });
    } catch(e) {
      wrap.innerHTML = `<div class="muted" style="font-size:12px">Error: ${esc(e.message)}</div>`;
    }
  }

  // ── Cobrar orden activa ───────────────────────────
  $('btnConfirmarCobro')?.addEventListener('click', async () => {
    const btn = $('btnConfirmarCobro');
    btn.disabled = true;
    btn.textContent = 'Procesando…';
    try {
      const r = await fetch(`${API}/autolavado/cobrar.php`, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id: state.ordenActivaId, metodo_pago: state.metodoActiva }),
      });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      modalClose('modalCobrarActiva');
      toast('Cobrado', `Folio ${j.folio}`, {icon:'🧾'});
      window.open(`ticket.php?id=${j.venta_id}`, '_blank');
      cargarActivas();
    } catch(e) {
      toast('Error', e.message, {icon:'⚠️', ttl:4000});
    } finally {
      btn.disabled = false;
      btn.textContent = 'Cobrar';
    }
  });

  $('btnRefreshActivas')?.addEventListener('click', cargarActivas);
  $('chkTodasFechas')?.addEventListener('change', cargarActivas);



  // ── Init ──────────────────────────────────────────
  cargarServicios();
  cargarActivas();

})();
</script>
