<?php
// pages/ordenes.php — también usado por taller.php
declare(strict_types=1);
require_once __DIR__ . '/../config/db.php';

// Cargar clientes para el select del modal
$clientes = db()->query("SELECT id, nombre_completo, telefono FROM clientes WHERE activo=1 ORDER BY nombre_completo")->fetchAll(PDO::FETCH_ASSOC);

// Cargar servicios de taller para el modal
$serviciosTaller = db()->query("SELECT id, nombre, precio FROM servicios WHERE tipo='taller' AND activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);

// Cargar productos para el modal
$productos = db()->query("SELECT id, nombre, precio AS precio_venta, stock_actual FROM productos WHERE activo=1 ORDER BY nombre")->fetchAll(PDO::FETCH_ASSOC);
?>
<link rel="stylesheet" href="css/ordenes.css?v=2">
<script src="https://cdn.jsdelivr.net/npm/chart.js@4"></script>

<div style="display:flex;align-items:center;justify-content:space-between;gap:12px;margin-bottom:12px;">
  <div>
    <div style="font-weight:900;letter-spacing:.4px;">Órdenes de trabajo — Taller</div>
    <div class="muted" style="font-size:12px;">Recibe, diagnostica, entrega y cobra</div>
  </div>
  <div style="display:flex;gap:10px;flex-wrap:wrap;align-items:center;">
    <button class="btn btn--ghost" id="btnRefrescar" type="button">↻ Refrescar</button>
    <button class="btn btn--ghost" id="btnEliminarVencidas" type="button" title="Elimina órdenes viejas que nunca se cerraron (no entregadas ni canceladas)">🗑 Eliminar vencidas</button>
    <button class="btn" id="btnNuevaOrden" type="button">+ Nueva orden</button>
  </div>
</div>

<!-- Resumen / gráficas del taller -->
<div class="ot-cards" style="margin-bottom:14px">
  <div class="ot-card" style="display:flex;align-items:center;gap:14px">
    <div style="width:110px;height:110px;flex-shrink:0"><canvas id="chartEstados"></canvas></div>
    <div style="display:flex;flex-direction:column;gap:6px;font-size:12px" id="leyendaEstados"></div>
  </div>
  <div class="ot-card">
    <div class="ot-card__title">Ahora mismo</div>
    <div class="ot-kv" style="font-size:20px;margin-top:4px"><b id="kpiEnTaller">0</b> <span class="muted" style="font-size:12px">vehículos en el taller</span></div>
    <div class="ot-kv" style="margin-top:6px"><span class="muted">Sin pagar:</span> <b id="kpiSinPagar">0</b></div>
    <div class="ot-kv"><span class="muted">Esperando entrega:</span> <b id="kpiEsperandoEntrega">0</b></div>
  </div>
  <div class="ot-card">
    <div class="ot-card__title">Extras por revisar</div>
    <div class="ot-kv" style="font-size:20px;margin-top:4px"><b id="kpiExtrasPend">0</b> <span class="muted" style="font-size:12px">pendientes de aprobación</span></div>
    <div class="xs muted" style="margin-top:6px">Recargo por dificultad (ej. moto grande/sucia) propuesto desde la app móvil.</div>
  </div>
</div>

<div class="ot-layout">

  <!-- LISTADO -->
  <section class="ot-panel">
    <div class="ot-panel__head">
      <div style="font-weight:800;">Listado</div>
      <div class="ot-filters">
        <select id="fEstado" class="select" style="font-size:12px;padding:6px 10px;border-radius:10px">
          <option value="">Todos los estados</option>
          <option value="recibido">Recibido</option>
          <option value="en_proceso">En proceso</option>
          <option value="terminado">Terminado</option>
          <option value="entregado">Entregado</option>
          <option value="cancelado">Cancelado</option>
        </select>
        <input id="fQ" class="select" placeholder="Folio o cliente…" style="font-size:12px;padding:6px 10px;border-radius:10px;width:140px">
        <button class="btn btn--ghost" id="btnBuscar" type="button" style="padding:6px 12px;font-size:12px">Buscar</button>
      </div>
    </div>

    <div class="ot-tablewrap">
      <table class="ot-table">
        <thead>
          <tr>
            <th>Folio</th>
            <th>Cliente</th>
            <th>Estado</th>
            <th style="text-align:right">Total</th>
            <th></th>
          </tr>
        </thead>
        <tbody id="tbodyOrdenes">
          <tr><td colspan="5" class="muted" style="padding:20px;text-align:center">Cargando…</td></tr>
        </tbody>
      </table>
    </div>
  </section>

  <!-- DETALLE -->
  <section class="ot-panel">
    <div class="ot-panel__head">
      <div style="font-weight:800;">Detalle</div>
      <div style="display:flex;gap:8px;flex-wrap:wrap;" id="estadoBtns">
        <button class="btn btn--ghost" id="btnEnProceso"  type="button" disabled style="font-size:12px;padding:7px 12px">▶ En proceso</button>
        <button class="btn btn--ghost" id="btnTerminado"  type="button" disabled style="font-size:12px;padding:7px 12px">✓ Terminado</button>
        <button class="btn"           id="btnEntregado"  type="button" disabled style="font-size:12px;padding:7px 12px">📦 Entregar</button>
        <button class="btn btn--ghost" id="btnCancelar"   type="button" disabled style="font-size:12px;padding:7px 12px;color:#ff5a5a">✕ Cancelar</button>
      </div>
    </div>

    <div style="padding:12px;overflow-y:auto;max-height:calc(100vh - 180px)">

      <!-- Estado vacío -->
      <div id="detalleVacio" class="muted" style="text-align:center;padding:40px 0">
        Selecciona una orden para ver el detalle
      </div>

      <!-- Contenido del detalle -->
      <div id="detalleContenido" hidden>

        <!-- Cards resumen -->
        <div class="ot-cards">
          <div class="ot-card">
            <div class="ot-card__title">Orden</div>
            <div class="ot-kv"><span class="muted">Folio:</span> <b id="dFolio"></b></div>
            <div class="ot-kv"><span class="muted">Estado:</span> <span id="dEstadoPill"></span></div>
            <div class="ot-kv"><span class="muted">Pago:</span> <span id="dPagoPill"></span></div>
            <div class="ot-kv"><span class="muted">Entrada:</span> <span id="dEntrada" style="font-size:11px"></span></div>
            <div class="ot-kv"><span class="muted">KM:</span> <span id="dKm"></span></div>
            <button class="btn" id="btnCobrarOrden" type="button" style="margin-top:10px;width:100%;font-size:12px;padding:8px" disabled>💰 Cobrar</button>
          </div>
          <div class="ot-card">
            <div class="ot-card__title">Cliente / Vehículo</div>
            <div class="ot-kv"><span class="muted">Cliente:</span> <span id="dCliente"></span></div>
            <div class="ot-kv"><span class="muted">Tel:</span> <span id="dTel" style="font-size:11px"></span></div>
            <div class="ot-kv"><span class="muted">Vehículo:</span> <span id="dVehiculo"></span></div>
            <div class="ot-kv"><span class="muted">Placa:</span> <span id="dPlaca"></span></div>
          </div>
          <div class="ot-card">
            <div class="ot-card__title">Totales</div>
            <div class="ot-kv"><span class="muted">Servicios:</span> $<b id="tServ">0.00</b></div>
            <div class="ot-kv"><span class="muted">Productos:</span> $<b id="tProd">0.00</b></div>
            <div class="ot-kv" style="font-size:17px;margin-top:6px"><span class="muted">Total:</span> $<b id="tTotal">0.00</b></div>
          </div>
        </div>

        <!-- Problema / diagnóstico -->
        <div class="ot-box" style="margin-bottom:10px">
          <div class="ot-box__head"><b>Descripción del problema</b></div>
          <div class="ot-notes"><pre id="dProblema" class="ot-pre"></pre></div>
        </div>

        <!-- Servicios + Productos -->
        <div class="ot-two">
          <div class="ot-box">
            <div class="ot-box__head">
              <b>Servicios</b>
              <button class="btn btn--ghost" id="btnAddServicio" type="button" style="font-size:12px;padding:6px 10px">+ Agregar</button>
            </div>
            <div class="ot-tablewrap" style="max-height:200px">
              <table class="ot-table">
                <thead><tr><th>Servicio</th><th style="text-align:right">Cant</th><th style="text-align:right">Subtotal</th></tr></thead>
                <tbody id="tbodyServicios"></tbody>
              </table>
            </div>
          </div>

          <div class="ot-box">
            <div class="ot-box__head">
              <b>Productos</b>
              <button class="btn btn--ghost" id="btnAddProducto" type="button" style="font-size:12px;padding:6px 10px">+ Agregar</button>
            </div>
            <div class="ot-tablewrap" style="max-height:200px">
              <table class="ot-table">
                <thead><tr><th>Producto</th><th style="text-align:right">Cant</th><th style="text-align:right">Subtotal</th></tr></thead>
                <tbody id="tbodyProductos"></tbody>
              </table>
            </div>
          </div>
        </div>

        <!-- Extras (recargo por dificultad) -->
        <div class="ot-box" style="margin-top:10px">
          <div class="ot-box__head"><b>Extras por dificultad</b></div>
          <div id="listaExtras" style="padding:10px">
            <div class="muted" style="font-size:12px">Sin extras.</div>
          </div>
        </div>

      </div><!-- /detalleContenido -->
    </div>
  </section>
</div><!-- /ot-layout -->

<!-- ══════════ MODALES ══════════ -->

<!-- Modal: Nueva orden -->
<div class="modal" id="modalNuevaOrden">
  <div class="modal__backdrop" data-modal-close="modalNuevaOrden"></div>
  <div class="modal__dialog" style="max-width:560px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Nueva orden de trabajo</h3>
        <p class="modal__subtitle">Taller — se crea con estado "recibido"</p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalNuevaOrden">✕</button>
    </div>
    <div class="modal__body">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">

        <div class="field" style="grid-column:1/-1">
          <label>Cliente *</label>
          <select class="select" id="nCliente" style="width:100%;padding:10px 12px;border-radius:14px">
            <option value="">— Seleccionar cliente —</option>
            <?php foreach ($clientes as $c): ?>
              <option value="<?= $c['id'] ?>"><?= htmlspecialchars($c['nombre_completo']) ?> · <?= htmlspecialchars($c['telefono']) ?></option>
            <?php endforeach; ?>
          </select>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label>Vehículo <span class="muted">(opcional)</span></label>
          <select class="select" id="nVehiculo" style="width:100%;padding:10px 12px;border-radius:14px" disabled>
            <option value="">— Primero elige cliente —</option>
          </select>
        </div>

        <div class="field">
          <label>Kilometraje entrada</label>
          <input class="select" id="nKm" type="number" min="0" placeholder="Ej: 23540" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>

        <div class="field">
          <label>Fecha entrada</label>
          <input class="select" id="nFecha" type="datetime-local" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>

        <div class="field" style="grid-column:1/-1">
          <label>Descripción del problema *</label>
          <textarea class="select" id="nProblema" rows="3" placeholder="Describe el problema que reporta el cliente…" style="width:100%;padding:10px 12px;border-radius:14px;resize:none"></textarea>
        </div>

        <div class="field" style="grid-column:1/-1">
          <label>Notas internas <span class="muted">(opcional)</span></label>
          <textarea class="select" id="nNotas" rows="2" placeholder="Notas para el mecánico…" style="width:100%;padding:10px 12px;border-radius:14px;resize:none"></textarea>
        </div>

      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalNuevaOrden">Cancelar</button>
      <button class="btn" id="btnCrearOrden" type="button">Crear orden</button>
    </div>
  </div>
</div>

<!-- Modal: Agregar servicio -->
<div class="modal" id="modalServicio">
  <div class="modal__backdrop" data-modal-close="modalServicio"></div>
  <div class="modal__dialog" style="max-width:420px" role="dialog">
    <div class="modal__header">
      <div><h3 class="modal__title">Agregar servicio</h3></div>
      <button class="modal__close" type="button" data-modal-close="modalServicio">✕</button>
    </div>
    <div class="modal__body">
      <div class="field" style="margin-bottom:12px">
        <label>Servicio *</label>
        <select class="select" id="sServicio" style="width:100%;padding:10px 12px;border-radius:14px">
          <option value="">— Seleccionar —</option>
          <?php foreach ($serviciosTaller as $s): ?>
            <option value="<?= $s['id'] ?>" data-precio="<?= $s['precio'] ?>">
              <?= htmlspecialchars($s['nombre']) ?> — $<?= number_format((float)$s['precio'], 2) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field" style="margin-bottom:12px">
        <label>Cantidad</label>
        <input class="select" id="sCantidad" type="number" min="1" value="1" style="width:100%;padding:10px 12px;border-radius:14px">
      </div>
      <div class="field">
        <label>Precio unitario</label>
        <input class="select" id="sPrecio" type="number" min="0" step="0.01" value="0" style="width:100%;padding:10px 12px;border-radius:14px">
        <span class="xs muted" style="display:block;margin-top:4px;">Editable — útil para servicios "a cotizar" o con precio según el trabajo.</span>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalServicio">Cancelar</button>
      <button class="btn" id="btnGuardarServicio" type="button">Agregar</button>
    </div>
  </div>
</div>

<!-- Modal: Agregar producto -->
<div class="modal" id="modalProducto">
  <div class="modal__backdrop" data-modal-close="modalProducto"></div>
  <div class="modal__dialog" style="max-width:420px" role="dialog">
    <div class="modal__header">
      <div><h3 class="modal__title">Agregar producto</h3></div>
      <button class="modal__close" type="button" data-modal-close="modalProducto">✕</button>
    </div>
    <div class="modal__body">
      <div class="field" style="margin-bottom:12px">
        <label>Producto *</label>
        <select class="select" id="pProducto" style="width:100%;padding:10px 12px;border-radius:14px">
          <option value="">— Seleccionar —</option>
          <?php foreach ($productos as $p): ?>
            <option value="<?= $p['id'] ?>" data-precio="<?= $p['precio_venta'] ?>">
              <?= htmlspecialchars($p['nombre']) ?> (stock: <?= (int)$p['stock_actual'] ?>) — $<?= number_format((float)$p['precio_venta'], 2) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </div>
      <div class="field">
        <label>Cantidad</label>
        <input class="select" id="pCantidad" type="number" min="1" value="1" style="width:100%;padding:10px 12px;border-radius:14px">
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalProducto">Cancelar</button>
      <button class="btn" id="btnGuardarProducto" type="button">Agregar</button>
    </div>
  </div>
</div>

<!-- Modal: Cobrar orden -->
<div class="modal" id="modalCobrarOrden">
  <div class="modal__backdrop" data-modal-close="modalCobrarOrden"></div>
  <div class="modal__dialog" style="max-width:400px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Cobrar orden</h3>
        <p class="modal__subtitle" id="cobrarOrdenInfo"></p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalCobrarOrden">✕</button>
    </div>
    <div class="modal__body">
      <div class="pos__label" style="text-transform:uppercase;font-size:11px;color:var(--muted);margin-bottom:8px">Método de pago</div>
      <div class="metodos-grid" style="display:grid;grid-template-columns:repeat(3,1fr);gap:8px">
        <button class="metodo-btn is-sel" data-pago-orden="efectivo"      type="button">💵 Efectivo</button>
        <button class="metodo-btn"        data-pago-orden="tarjeta"       type="button">💳 Tarjeta</button>
        <button class="metodo-btn"        data-pago-orden="transferencia" type="button">📲 Transfer</button>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalCobrarOrden">Cancelar</button>
      <button class="btn" id="btnConfirmarCobroOrden" type="button">Cobrar</button>
    </div>
  </div>
</div>

<!-- Modal: Confirmar cambio de estado -->
<div class="modal" id="modalEstado">
  <div class="modal__backdrop" data-modal-close="modalEstado"></div>
  <div class="modal__dialog" style="max-width:420px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title" id="estadoModalTitle">Cambiar estado</h3>
        <p class="modal__subtitle" id="estadoModalSub"></p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalEstado">✕</button>
    </div>
    <div class="modal__body">
      <div class="field">
        <label>Nota <span class="muted">(opcional)</span></label>
        <textarea class="select" id="estadoNota" rows="3" placeholder="Ej: Se inició diagnóstico…" style="width:100%;padding:10px 12px;border-radius:14px;resize:none"></textarea>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalEstado">Cancelar</button>
      <button class="btn" id="btnConfirmarEstado" type="button">Confirmar</button>
    </div>
  </div>
</div>
