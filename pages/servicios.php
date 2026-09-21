<?php declare(strict_types=1); ?>

<div class="table-wrap" id="serviciosWrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Servicios</div>
      <span class="pill" id="serviciosCount">—</span>
    </div>

    <div class="table-toolbar__right">
      <select class="select" id="serviciosFiltroTipo" style="height:42px;">
        <option value="">Todos</option>
        <option value="taller">Taller</option>
        <option value="autolavado">Autolavado</option>
      </select>

      <input class="input" id="serviciosSearch" type="search" placeholder="Buscar…" />

      <label class="pill" style="display:flex; gap:8px; align-items:center; cursor:pointer;">
        <input type="checkbox" id="serviciosMostrarInactivos" />
        Mostrar inactivos
      </label>

      <button class="btn" type="button" id="btnNuevoServicio">+ Nuevo</button>
    </div>
  </div>

  <table class="table" id="serviciosTable">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Tipo</th>
        <th class="num">Precio</th>
        <th class="num">Costo insumos</th>
        <th class="num">Tiempo</th>
        <th>Estado</th>
        <th class="num">Acciones</th>
      </tr>
    </thead>
    <tbody id="serviciosTbody">
      <tr><td colspan="7" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</div>

<!-- MODAL: NUEVO SERVICIO -->
<div class="modal" id="modalServicio" aria-hidden="true">
  <div class="modal__backdrop"></div>

  <div class="modal__dialog" role="dialog" aria-modal="true">
    <div class="modal__header">
      <div>
        <h3 class="modal__title" id="srvModalTitle">Nuevo servicio</h3>
        <p class="modal__subtitle">Se guarda en la base de datos.</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalServicio">✕</button>
    </div>

    <div class="modal__body">
      <input type="hidden" id="srvId" value="0" />
      <div class="form-grid">
        <div>
          <div class="field">
            <label>Nombre *</label>
            <input class="input2" id="srvNombre" placeholder="Ej. Lavado premium" />
          </div>

          <div class="field" style="margin-top:10px;">
            <label>Descripción</label>
            <input class="input2" id="srvDesc" placeholder="(opcional)" />
          </div>

          <div class="field" style="margin-top:10px;">
            <label>Tipo *</label>
            <select class="select" id="srvTipo">
              <option value="autolavado">autolavado</option>
              <option value="taller">taller</option>
            </select>
          </div>
        </div>

        <div>
          <div class="field">
            <label>Precio *</label>
            <input class="input2" id="srvPrecio" type="number" min="0" step="0.01" value="0" style="text-align:right" />
          </div>

          <div class="field" style="margin-top:10px;">
            <label>Tiempo estimado (min)</label>
            <input class="input2" id="srvTiempo" type="number" min="0" step="1" value="0" style="text-align:right" />
          </div>

          <div class="field" style="margin-top:10px;">
            <label>Activo</label>
            <select class="select" id="srvActivo">
              <option value="1">Sí</option>
              <option value="0">No</option>
            </select>
          </div>
        </div>
      </div>

      <div id="srvInsumosBloqueVacio" class="muted" style="margin-top:18px; padding-top:14px; border-top:1px solid var(--panel2);">
        Guarda el servicio primero para poder agregar los insumos que consume.
      </div>

      <div id="srvInsumosBloque" style="margin-top:18px; padding-top:14px; border-top:1px solid var(--panel2); display:none;">
        <label style="display:block; margin-bottom:8px;">Insumos utilizados</label>

        <div style="display:flex; gap:8px; align-items:flex-end; flex-wrap:wrap;">
          <div class="field" style="flex:1; min-width:180px;">
            <label>Producto</label>
            <select class="select" id="srvInsumoProducto"></select>
          </div>
          <div class="field" style="width:110px;">
            <label>Cantidad</label>
            <input class="input2" id="srvInsumoCantidad" type="number" min="0" step="0.01" value="1" style="text-align:right" />
          </div>
          <button class="btn btn--ghost" type="button" id="btnAgregarInsumo">Agregar</button>
        </div>

        <table class="table" style="margin-top:10px;">
          <thead>
            <tr>
              <th>Producto</th>
              <th class="num">Cantidad</th>
              <th class="num">Costo unit.</th>
              <th class="num">Subtotal</th>
              <th class="num">Quitar</th>
            </tr>
          </thead>
          <tbody id="srvInsumosTbody">
            <tr><td colspan="5" class="muted">Sin insumos agregados.</td></tr>
          </tbody>
        </table>

        <div style="text-align:right; margin-top:8px; font-weight:600;">
          Costo total: <span id="srvInsumosCostoTotal">$0.00</span>
        </div>
      </div>
    </div>

    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalServicio">Cancelar</button>
      <button class="btn" type="button" id="btnGuardarServicio">Guardar</button>
    </div>
  </div>
</div>
