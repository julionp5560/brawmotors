<?php declare(strict_types=1); ?>

<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Inventario</div>
      <div class="field">
  <label>Categoría</label>
  <select class="select" id="prodCategoriaId"></select>
</div>

      <span class="pill" id="invCount">—</span>
      <span class="pill pill--warn" id="invBajoCount" style="display:none;">Stock bajo: 0</span>
    </div>

    <div class="table-toolbar__right">
      <input class="input" id="invSearch" placeholder="Buscar (nombre, sku, categoría)..." />
      <label class="pill" style="display:flex; gap:8px; align-items:center;">
        <input type="checkbox" id="invSoloBajo" />
        Solo stock bajo
      </label>
      <label class="pill" style="display:flex; gap:8px; align-items:center; cursor:pointer;">
        <input type="checkbox" id="invMostrarInactivos" />
        Mostrar inactivos
      </label>
      <button class="btn" type="button" id="btnNuevoProducto">Nuevo producto</button>
      <button class="btn" type="button" id="btnNuevoMov">Movimiento</button>
    </div>
  </div>

  <div class="hr"></div>

  <table class="table">
    <thead>
      <tr>
        <th>Producto</th>
        <th>Categoría</th>
        <th class="num">Costo</th>
        <th class="num">Precio</th>
        <th class="num">Stock</th>
        <th class="num">Mín</th>
        <th>Estado</th>
        <th class="num">Acciones</th>
      </tr>
    </thead>
    <tbody id="invTbody">
      <tr><td colspan="8" class="muted">Cargando…</td></tr>
    </tbody>
  </table>

  <div class="hr"></div>

  <div style="display:flex; justify-content:space-between; align-items:center; gap:10px;">
    <div>
      <div class="muted">Kardex (últimos 200 movimientos)</div>
      <div class="xs muted">Tip: filtra por producto desde “Acciones → Kardex”.</div>
    </div>
    <div class="row-actions">
      <button class="btn-sm" type="button" id="btnKardexTodos">Ver todo</button>
    </div>
  </div>

  <table class="table" style="margin-top:10px;">
    <thead>
      <tr>
        <th>Fecha</th>
        <th>Producto</th>
        <th>Tipo</th>
        <th class="num">Cantidad</th>
        <th>Referencia</th>
        <th>Nota</th>
      </tr>
    </thead>
    <tbody id="movTbody">
      <tr><td colspan="6" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</div>

<!-- MODAL PRODUCTO -->
<div class="modal" id="modalProducto">
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" role="dialog" aria-modal="true" style="max-width:720px;">
    <div class="modal__header">
      <div>
        <h3 class="modal__title" id="prodTitle">Producto</h3>
        <p class="modal__subtitle">CRUD de producto (solo unidades).</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalProducto">✕</button>
    </div>
    <div class="modal__body">
      <input type="hidden" id="prodId" value="0" />
      <div class="grid2">
        <div class="field">
          <label>SKU (opcional)</label>
          <input class="input2" id="prodSku" />
        </div>
        <div class="field">
          <label>Nombre *</label>
          <input class="input2" id="prodNombre" />
        </div>
        <div class="field">
          <label>Categoría</label>
          <select class="select" id="prodCategoriaId"></select>
        </div>

        <div class="field">
          <label>Costo</label>
          <input class="input2" id="prodCosto" type="number" step="0.01" value="0" />
        </div>
        <div class="field">
          <label>Precio venta</label>
          <input class="input2" id="prodPrecio" type="number" step="0.01" value="0" />
        </div>
        <div class="field">
          <label>Stock mínimo</label>
          <input class="input2" id="prodMin" type="number" step="1" value="0" />
        </div>
        <div class="field">
          <label>Stock inicial (solo al crear)</label>
          <input class="input2" id="prodStockIni" type="number" step="1" value="0" />
        </div>
        <div class="field">
          <label>Activo</label>
          <select class="select" id="prodActivo">
            <option value="1">Sí</option>
            <option value="0">No</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalProducto">Cerrar</button>
      <button class="btn" type="button" id="btnGuardarProducto">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL MOVIMIENTO -->
<div class="modal" id="modalMov">
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" role="dialog" aria-modal="true" style="max-width:720px;">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Movimiento de inventario</h3>
        <p class="modal__subtitle">Entrada, salida o ajuste (en unidades).</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalMov">✕</button>
    </div>
    <div class="modal__body">
      <div class="grid2">
        <div class="field">
          <label>Producto *</label>
          <select class="select" id="movProducto"></select>
        </div>
        <div class="field">
          <label>Tipo *</label>
          <select class="select" id="movTipo">
            <option value="ENTRADA">ENTRADA (+)</option>
            <option value="SALIDA">SALIDA (-)</option>
            <option value="AJUSTE">AJUSTE (+/-)</option>
          </select>
        </div>
        <div class="field">
          <label>Cantidad *</label>
          <input class="input2" id="movCantidad" type="number" step="1" value="1" />
          <div class="xs muted">En AJUSTE puedes poner negativo (ej. -2).</div>
        </div>
        <div class="field">
          <label>Referencia</label>
          <input class="input2" id="movRef" placeholder="Ej. Factura 123 / Ajuste / Merma" />
        </div>
        <div class="field" style="grid-column:1/-1;">
          <label>Nota</label>
          <input class="input2" id="movNota" placeholder="Opcional" />
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalMov">Cerrar</button>
      <button class="btn" type="button" id="btnGuardarMov">Guardar movimiento</button>
    </div>
  </div>
</div>
<div class="modal" id="modalCategorias">
  <div class="modal__backdrop"></div>
  <div class="modal__dialog" role="dialog" aria-modal="true" style="max-width:720px;">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Categorías</h3>
        <p class="modal__subtitle">Administra las categorías del inventario.</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalCategorias">✕</button>
    </div>

    <div class="modal__body">
      <div class="grid2">
        <div class="field" style="grid-column:1/-1;">
          <label>Nueva categoría</label>
          <div style="display:flex; gap:10px;">
            <input class="input2" id="catNombre" placeholder="Ej. Lubricantes" />
            <button class="btn" type="button" id="btnCatCrear">Crear</button>
          </div>
        </div>
      </div>

      <div class="hr"></div>

      <table class="table">
        <thead>
          <tr><th>Nombre</th><th class="num">Acciones</th></tr>
        </thead>
        <tbody id="catTbody">
          <tr><td colspan="2" class="muted">Cargando…</td></tr>
        </tbody>
      </table>
    </div>

    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalCategorias">Cerrar</button>
    </div>
  </div>
</div>
