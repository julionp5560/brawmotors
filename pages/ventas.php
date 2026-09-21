<?php declare(strict_types=1); ?>

<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Ventas</div>
      <span class="pill" id="ventasCount">—</span>
    </div>

    <div class="table-toolbar__right">
      <input class="input" id="ventasSearch" type="search" placeholder="Filtrar…" />
      <button class="btn" type="button" id="btnNuevaVenta">+ Nueva venta</button>
    </div>
  </div>

  <table class="table" id="ventasTable">
    <thead>
      <tr>
        <th>Folio</th>
        <th>Fecha</th>
        <th>Cliente</th>
        <th>Pago</th>
        <th class="num">Total</th>
        <th>Estado</th>
        <th class="num">Acciones</th>
      </tr>
    </thead>
    <tbody id="ventasTbody">
      <tr><td colspan="7" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</div>

<!-- MODAL: NUEVA VENTA -->
<div class="modal" id="modalVenta" aria-hidden="true">
  <div class="modal__backdrop"></div>

  <div class="modal__dialog" role="dialog" aria-modal="true">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Nueva venta</h3>
        <p class="modal__subtitle">Agrega servicios desde BD y cobra.</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalVenta">✕</button>
    </div>

    <div class="modal__body">
      <div class="form-grid">
        <div>
          <div class="field">
            <label>Cliente</label>
            <select class="select" id="ventaCliente">
              <option>Mostrador</option>
            </select>
          </div>

          <div class="hr"></div>

          <div class="table-wrap" style="border-radius:16px; overflow:hidden;">
            <div class="table-toolbar" style="border-bottom:1px solid var(--border);">
              <div class="table-toolbar__left">
                <div class="table-toolbar__title">Items</div>
                <span class="pill">Servicios</span>
              </div>
              <div class="table-toolbar__right">
                <button class="btn-sm" type="button" id="btnAddItem">+ Agregar</button>
              </div>
            </div>

            <table class="table">
              <thead>
                <tr>
                  <th>Servicio</th>
                  <th class="num">Cant</th>
                  <th class="num">Precio</th>
                  <th class="num">Importe</th>
                  <th class="num"></th>
                </tr>
              </thead>
              <tbody id="ventaItems"></tbody>
            </table>
          </div>
        </div>

        <div>
          <div class="summary">
            <div class="summary__row">
              <div class="summary__label">Subtotal</div>
              <div class="summary__value">$<span id="sumSubtotal">0.00</span></div>
            </div>

            <div class="summary__row">
              <div class="summary__label">
                IVA (%)
                <input id="ivaRate" class="input2" type="number" value="0" min="0" step="0.01" style="width:90px; margin-left:8px; text-align:right;">
              </div>
              <div class="summary__value">$<span id="sumIva">0.00</span></div>
            </div>

            <div class="summary__row">
              <div class="summary__label">Total</div>
              <div class="summary__value">$<span id="sumTotal">0.00</span></div>
            </div>

            <div class="hr"></div>

            <div class="field">
              <label>Método de pago</label>
              <select class="select" id="ventaPago">
                <option value="efectivo">Efectivo</option>
                <option value="tarjeta">Tarjeta</option>
                <option value="transferencia">Transferencia</option>
              </select>
            </div>

            <div class="field" style="margin-top:10px;">
              <label>Notas</label>
              <input class="input2" id="ventaNotas" placeholder="(opcional)" />
            </div>
          </div>
        </div>
      </div>
    </div>

    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalVenta">Cancelar</button>
      <button class="btn" type="button" id="btnCobrar">Cobrar</button>
    </div>
  </div>
</div>
<!-- MODAL: CANCELAR VENTA -->
<div class="modal" id="modalCancelarVenta" aria-hidden="true">
  <div class="modal__backdrop"></div>

  <div class="modal__dialog" role="dialog" aria-modal="true" style="max-width:560px;">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Cancelar venta</h3>
        <p class="modal__subtitle">Esto no borra la venta, solo la marca como cancelada y ya no suma en corte.</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalCancelarVenta">✕</button>
    </div>

    <div class="modal__body">
      <input type="hidden" id="cancelVentaId" value="0">

      <div class="field">
        <label>Motivo de cancelación *</label>
        <input class="input2" id="cancelMotivo" placeholder="Ej. Cobro duplicado / Error de monto / Cliente no quiso" />
      </div>

      <div class="hr"></div>

      <div class="xs muted">
        Tip: si fue un error de captura, cancela y vuelve a generar la venta correcta.
      </div>
    </div>

    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalCancelarVenta">Cerrar</button>
      <button class="btn" type="button" id="btnConfirmarCancelacion">Confirmar cancelación</button>
    </div>
  </div>
</div>
