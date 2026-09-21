// js/taller.js
(() => {
  'use strict';

  const API = 'php/api/ordenes';
  const esc = s => String(s ?? '').replace(/&/g, '&amp;').replace(/</g, '&lt;').replace(/>/g, '&gt;');
  const $ = id => document.getElementById(id);
  const fmt = n => Number(n || 0).toFixed(2);

  if (!$('tbodyOrdenes')) return;

  let ordenActual = null;
  let estadoPendiente = '';
  let metodoPagoOrden = 'efectivo';

  const ESTADO_LABEL = {
    recibido: 'Recibido',
    en_proceso: 'En proceso',
    terminado: 'Terminado',
    entregado: 'Entregado',
    cancelado: 'Cancelado',
  };
  const ESTADO_CLASS = {
    recibido: 'tag--warn',
    en_proceso: 'tag--ok',
    terminado: 'tag',
    entregado: 'tag',
    cancelado: 'tag--bad',
  };

  async function listar() {
    const estado = $('fEstado')?.value || '';
    const q = $('fQ')?.value.trim() || '';
    const params = new URLSearchParams({ tipo: 'taller' });
    if (estado) params.set('estado', estado);
    if (q) params.set('q', q);

    $('tbodyOrdenes').innerHTML = '<tr><td colspan="5" class="muted" style="padding:20px;text-align:center">Cargando...</td></tr>';
    try {
      const r = await fetch(API + '/listar.php?' + params);
      const j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);
      const rows = j.data || [];
      if (!rows.length) {
        $('tbodyOrdenes').innerHTML = '<tr><td colspan="5" class="muted" style="padding:20px;text-align:center">Sin ordenes.</td></tr>';
        return;
      }
      $('tbodyOrdenes').innerHTML = rows.map(function (o) {
        return '<tr style="cursor:pointer" data-oid="' + o.id + '">' +
          '<td><b>' + esc(o.folio) + '</b></td>' +
          '<td style="max-width:120px;overflow:hidden;text-overflow:ellipsis">' + esc(o.cliente_nombre) + '</td>' +
          '<td><span class="tag ' + (ESTADO_CLASS[o.estado] || 'tag') + '" style="font-size:11px">' + esc(ESTADO_LABEL[o.estado] || o.estado) + '</span></td>' +
          '<td style="text-align:right">$' + fmt(o.total) + '</td>' +
          '<td style="text-align:right"><button class="btn-sm" data-ver="' + o.id + '" type="button">Ver</button></td>' +
          '</tr>';
      }).join('');

      $('tbodyOrdenes').querySelectorAll('tr[data-oid]').forEach(function (tr) {
        tr.addEventListener('click', function () { cargarDetalle(Number(tr.dataset.oid)); });
      });
    } catch (e) {
      $('tbodyOrdenes').innerHTML = '<tr><td colspan="5" class="muted" style="padding:20px">Error: ' + esc(e.message) + '</td></tr>';
    }
  }

  async function cargarDetalle(id) {
    $('detalleVacio').hidden = true;
    $('detalleContenido').hidden = false;
    ['btnEnProceso', 'btnTerminado', 'btnEntregado', 'btnCancelar', 'btnAddServicio', 'btnAddProducto']
      .forEach(function (b) { var el = $(b); if (el) el.disabled = true; });

    try {
      const r = await fetch(API + '/detalle.php?id=' + id);
      const j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);
      const orden = j.data.orden;
      const servicios = j.data.servicios;
      const productos = j.data.productos;
      const totales = j.data.totales;
      ordenActual = orden;

      $('dFolio').textContent = orden.folio || '';
      $('dEstadoPill').innerHTML = '<span class="tag ' + (ESTADO_CLASS[orden.estado] || 'tag') + '" style="font-size:11px">' + (ESTADO_LABEL[orden.estado] || orden.estado) + '</span>';
      var pagado = Number(orden.pagado) === 1;
      $('dPagoPill').innerHTML = pagado
        ? '<span class="tag tag--ok" style="font-size:11px">✓ Pagado</span>'
        : '<span class="tag tag--warn" style="font-size:11px">⏳ Sin pagar</span>';
      var btnCobrar = $('btnCobrarOrden');
      if (btnCobrar) btnCobrar.disabled = pagado || orden.estado === 'cancelado';
      $('dEntrada').textContent = (orden.fecha_entrada || '').slice(0, 16).replace('T', ' ');
      $('dKm').textContent = orden.kilometraje_entrada ? orden.kilometraje_entrada + ' km' : '-';
      $('dCliente').textContent = orden.cliente_nombre || '-';
      $('dTel').textContent = orden.cliente_telefono || '-';
      $('dVehiculo').textContent = [orden.vehiculo_marca, orden.vehiculo_modelo, orden.vehiculo_anio].filter(Boolean).join(' ') || '-';
      $('dPlaca').textContent = orden.vehiculo_placas || '-';
      $('dProblema').textContent = orden.descripcion_problema || '';
      $('tServ').textContent = fmt(totales.total_servicios);
      $('tProd').textContent = fmt(totales.total_productos);
      $('tTotal').textContent = fmt(totales.total_calculado);

      $('tbodyServicios').innerHTML = servicios.length
        ? servicios.map(function (s) { return '<tr><td>' + esc(s.servicio_nombre) + '</td><td style="text-align:right">' + s.cantidad + '</td><td style="text-align:right">$' + fmt(s.subtotal) + '</td></tr>'; }).join('')
        : '<tr><td colspan="3" class="muted" style="padding:10px">Sin servicios.</td></tr>';

      $('tbodyProductos').innerHTML = productos.length
        ? productos.map(function (p) { return '<tr><td>' + esc(p.producto_nombre) + '</td><td style="text-align:right">' + p.cantidad + '</td><td style="text-align:right">$' + fmt(p.subtotal) + '</td></tr>'; }).join('')
        : '<tr><td colspan="3" class="muted" style="padding:10px">Sin productos.</td></tr>';

      renderExtras(j.data.extras || []);
      actualizarBotonesEstado(orden.estado);
    } catch (e) {
      window.toast && window.toast('Error', e.message, { icon: '!' });
    }
  }

  function actualizarBotonesEstado(estado) {
    var cerrado = estado === 'cancelado' || estado === 'entregado';
    // El cobro es independiente del flujo de trabajo: se puede cobrar en
    // cualquier momento (normalmente al entregar), no es requisito para
    // iniciar el trabajo.
    $('btnEnProceso').disabled = cerrado || estado === 'en_proceso' || estado === 'terminado' || estado === 'entregado';
    $('btnTerminado').disabled = cerrado || estado === 'recibido' || estado === 'terminado' || estado === 'entregado';
    $('btnEntregado').disabled = estado !== 'terminado';
    $('btnCancelar').disabled = cerrado;
    $('btnAddServicio').disabled = cerrado;
    $('btnAddProducto').disabled = cerrado;
  }

  var ESTADO_EXT_LABEL = { pendiente: 'En revisión', aprobado: 'Aprobado', rechazado: 'Rechazado' };

  function renderExtras(extras) {
    var box = $('listaExtras');
    if (!box) return;
    if (!extras.length) {
      box.innerHTML = '<div class="muted" style="font-size:12px">Sin extras.</div>';
      return;
    }
    box.innerHTML = extras.map(function (ex) {
      var esPendiente = ex.estado === 'pendiente';
      var monto = esPendiente ? Number(ex.monto_sugerido) : Number(ex.monto_final || 0);
      return '<div class="extra-review" data-exid="' + ex.id + '" style="background:rgba(0,0,0,.18);border:1px solid var(--border);border-radius:10px;padding:8px 10px;font-size:12px;margin-bottom:6px">' +
        '<div style="display:flex;justify-content:space-between;align-items:center;gap:8px;margin-bottom:6px">' +
        '<span style="font-weight:800">$' + fmt(monto) + (esPendiente ? ' sugerido' : '') + '</span>' +
        '<span class="tag ' + (ex.estado === 'aprobado' ? 'tag--ok' : ex.estado === 'rechazado' ? 'tag--bad' : 'tag--warn') + '" style="font-size:10px">' + (ESTADO_EXT_LABEL[ex.estado] || ex.estado) + '</span>' +
        '</div>' +
        '<div class="muted" style="margin-bottom:6px">' + esc(ex.justificacion) + '</div>' +
        (esPendiente ? (
          '<div style="display:flex;gap:6px;align-items:center">' +
          '<input class="select" type="number" min="0" step="0.01" value="' + Number(ex.monto_sugerido).toFixed(2) + '" id="montoFinalOt-' + ex.id + '" style="width:100px;padding:6px 8px;border-radius:10px;font-size:12px">' +
          '<button class="btn-sm" data-aprobar-ext="' + ex.id + '" type="button">✓ Aprobar</button>' +
          '<button class="btn-sm" data-rechazar-ext="' + ex.id + '" type="button" style="color:#ff5a5a">✕ Rechazar</button>' +
          '</div>'
        ) : '') +
        '</div>';
    }).join('');

    box.querySelectorAll('[data-aprobar-ext]').forEach(function (b) {
      b.addEventListener('click', async function () {
        var exId = Number(b.getAttribute('data-aprobar-ext'));
        var monto = Number(($('montoFinalOt-' + exId) || {}).value || 0);
        try {
          var r = await fetch('php/api/ordenes/extras/revisar.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: exId, accion: 'aprobar', monto_final: monto }),
          });
          var j = await r.json();
          if (!j.ok) throw new Error(j.detail || j.error);
          window.toast && window.toast('Extra aprobado', '', { icon: 'OK' });
          cargarDetalle(ordenActual.id);
          listar();
        } catch (e) { window.toast && window.toast('Error', e.message, { icon: '!' }); }
      });
    });
    box.querySelectorAll('[data-rechazar-ext]').forEach(function (b) {
      b.addEventListener('click', async function () {
        var exId = Number(b.getAttribute('data-rechazar-ext'));
        try {
          var r = await fetch('php/api/ordenes/extras/revisar.php', {
            method: 'POST', headers: { 'Content-Type': 'application/json' },
            body: JSON.stringify({ id: exId, accion: 'rechazar' }),
          });
          var j = await r.json();
          if (!j.ok) throw new Error(j.detail || j.error);
          window.toast && window.toast('Extra rechazado', '', { icon: 'OK' });
          cargarDetalle(ordenActual.id);
          listar();
        } catch (e) { window.toast && window.toast('Error', e.message, { icon: '!' }); }
      });
    });
  }

  $('btnCobrarOrden') && $('btnCobrarOrden').addEventListener('click', function () {
    if (!ordenActual) return;
    metodoPagoOrden = 'efectivo';
    document.querySelectorAll('[data-pago-orden]').forEach(function (b) {
      b.classList.toggle('is-sel', b.getAttribute('data-pago-orden') === 'efectivo');
    });
    $('cobrarOrdenInfo').textContent = 'Orden ' + ordenActual.folio;
    window.modalOpen('modalCobrarOrden');
  });

  document.querySelectorAll('[data-pago-orden]').forEach(function (b) {
    b.addEventListener('click', function () {
      document.querySelectorAll('[data-pago-orden]').forEach(function (x) { x.classList.remove('is-sel'); });
      b.classList.add('is-sel');
      metodoPagoOrden = b.getAttribute('data-pago-orden');
    });
  });

  $('btnConfirmarCobroOrden') && $('btnConfirmarCobroOrden').addEventListener('click', async function () {
    if (!ordenActual) return;
    var btn = $('btnConfirmarCobroOrden');
    btn.disabled = true;
    btn.textContent = 'Procesando…';
    try {
      var r = await fetch('php/api/ordenes/cobrar.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ id: ordenActual.id, metodo_pago: metodoPagoOrden }),
      });
      var j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);
      window.modalClose('modalCobrarOrden');
      window.toast && window.toast('Cobrado', 'Folio ' + j.folio, { icon: 'OK' });
      window.open('ticket.php?id=' + j.venta_id, '_blank');
      cargarDetalle(ordenActual.id);
      listar();
    } catch (e) {
      window.toast && window.toast('Error', e.message, { icon: '!' });
    } finally {
      btn.disabled = false;
      btn.textContent = 'Cobrar';
    }
  });

  function pedirCambioEstado(nuevoEstado, titulo, sub) {
    estadoPendiente = nuevoEstado;
    $('estadoModalTitle').textContent = titulo;
    $('estadoModalSub').textContent = sub;
    $('estadoNota').value = '';
    window.modalOpen('modalEstado');
    setTimeout(function () { $('estadoNota') && $('estadoNota').focus(); }, 60);
  }

  $('btnEnProceso') && $('btnEnProceso').addEventListener('click', function () { pedirCambioEstado('en_proceso', 'Iniciar trabajo', 'Orden ' + (ordenActual && ordenActual.folio)); });
  $('btnTerminado') && $('btnTerminado').addEventListener('click', function () { pedirCambioEstado('terminado', 'Marcar terminado', 'Orden ' + (ordenActual && ordenActual.folio)); });
  $('btnEntregado') && $('btnEntregado').addEventListener('click', function () { pedirCambioEstado('entregado', 'Entregar al cliente', 'Orden ' + (ordenActual && ordenActual.folio)); });
  $('btnCancelar') && $('btnCancelar').addEventListener('click', function () { pedirCambioEstado('cancelado', 'Cancelar orden', 'Orden ' + (ordenActual && ordenActual.folio)); });

  $('btnConfirmarEstado') && $('btnConfirmarEstado').addEventListener('click', async function () {
    if (!ordenActual || !estadoPendiente) return;
    var nota = $('estadoNota').value.trim();
    try {
      var r = await fetch(API + '/cambiar_estado.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orden_id: ordenActual.id, estado: estadoPendiente, nota: nota }),
      });
      var j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);
      window.modalClose('modalEstado');
      window.toast && window.toast('Estado actualizado', ESTADO_LABEL[estadoPendiente], { icon: 'OK' });
      await cargarDetalle(ordenActual.id);
      listar();
    } catch (e) { window.toast && window.toast('Error', e.message, { icon: '!' }); }
  });

  $('btnNuevaOrden') && $('btnNuevaOrden').addEventListener('click', function () {
    $('nCliente').value = '';
    $('nVehiculo').innerHTML = '<option value="">Primero elige cliente</option>';
    $('nVehiculo').disabled = true;
    $('nKm').value = '';
    $('nProblema').value = '';
    $('nNotas').value = '';
    var now = new Date();
    $('nFecha').value = new Date(now - now.getTimezoneOffset() * 60000).toISOString().slice(0, 16);
    window.modalOpen('modalNuevaOrden');
    setTimeout(function () { $('nCliente') && $('nCliente').focus(); }, 60);
  });

  $('nCliente') && $('nCliente').addEventListener('change', async function () {
    var cid = Number($('nCliente').value);
    var sel = $('nVehiculo');
    if (!cid) { sel.innerHTML = '<option value="">Primero elige cliente</option>'; sel.disabled = true; return; }
    sel.innerHTML = '<option value="">Cargando...</option>'; sel.disabled = true;
    try {
      var r = await fetch('php/api/vehiculos/list.php?cliente_id=' + cid);
      var j = await r.json();
      var vhs = j.data || [];
      sel.innerHTML = '<option value="">Sin vehiculo</option>' +
        vhs.map(function (v) { return '<option value="' + v.id + '">' + esc(v.marca) + ' ' + esc(v.modelo || '') + ' ' + (v.año || '') + (v.placas ? ' - ' + v.placas : '') + '</option>'; }).join('');
      sel.disabled = false;
    } catch (e) { sel.innerHTML = '<option value="">Error al cargar</option>'; }
  });

  $('btnCrearOrden') && $('btnCrearOrden').addEventListener('click', async function () {
    var cliente_id = Number($('nCliente').value);
    var vehiculo_id = Number($('nVehiculo').value) || null;
    var descripcion = $('nProblema').value.trim();
    var km = $('nKm').value ? Number($('nKm').value) : null;
    var fecha = $('nFecha').value || null;
    var notas = $('nNotas').value.trim() || null;
    if (!cliente_id) { window.toast && window.toast('Falta cliente', '', { icon: '!' }); return; }
    if (!descripcion) { window.toast && window.toast('Falta descripcion', '', { icon: '!' }); return; }
    try {
      var r = await fetch(API + '/crear.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ tipo: 'taller', cliente_id: cliente_id, vehiculo_id: vehiculo_id, descripcion_problema: descripcion, kilometraje_entrada: km, fecha_entrada: fecha, notas: notas, usuario_id: 1 }),
      });
      var j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);
      window.modalClose('modalNuevaOrden');
      window.toast && window.toast('Orden creada', j.data.folio, { icon: 'OK' });
      listar();
      cargarDetalle(j.data.id);
    } catch (e) { window.toast && window.toast('Error', e.message, { icon: '!' }); }
  });

  $('btnAddServicio') && $('btnAddServicio').addEventListener('click', function () {
    if (!ordenActual) return;
    $('sServicio').value = ''; $('sCantidad').value = '1'; $('sPrecio').value = '0';
    window.modalOpen('modalServicio');
  });

  $('sServicio') && $('sServicio').addEventListener('change', function () {
    var opt = $('sServicio').options[$('sServicio').selectedIndex];
    var precio = opt ? Number(opt.getAttribute('data-precio') || 0) : 0;
    $('sPrecio').value = precio.toFixed(2);
  });

  $('btnGuardarServicio') && $('btnGuardarServicio').addEventListener('click', async function () {
    if (!ordenActual) return;
    var servicio_id = Number($('sServicio').value);
    var cantidad = Number($('sCantidad').value) || 1;
    var precio_unitario = Number($('sPrecio').value);
    if (!servicio_id) { window.toast && window.toast('Elige un servicio', '', { icon: '!' }); return; }
    if (!(precio_unitario >= 0)) { window.toast && window.toast('Precio inválido', '', { icon: '!' }); return; }
    try {
      var r = await fetch(API + '/agregar_servicio.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orden_id: ordenActual.id, servicio_id: servicio_id, cantidad: cantidad, precio_unitario: precio_unitario }),
      });
      var j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);
      window.modalClose('modalServicio');
      window.toast && window.toast('Servicio agregado', j.data.servicio.nombre, { icon: 'OK' });
      cargarDetalle(ordenActual.id);
    } catch (e) { window.toast && window.toast('Error', e.message, { icon: '!' }); }
  });

  $('btnAddProducto') && $('btnAddProducto').addEventListener('click', function () {
    if (!ordenActual) return;
    $('pProducto').value = ''; $('pCantidad').value = '1';
    window.modalOpen('modalProducto');
  });

  $('btnGuardarProducto') && $('btnGuardarProducto').addEventListener('click', async function () {
    if (!ordenActual) return;
    var producto_id = Number($('pProducto').value);
    var cantidad = Number($('pCantidad').value) || 1;
    if (!producto_id) { window.toast && window.toast('Elige un producto', '', { icon: '!' }); return; }
    try {
      var r = await fetch(API + '/agregar_producto.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ orden_id: ordenActual.id, producto_id: producto_id, cantidad: cantidad }),
      });
      var j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);
      window.modalClose('modalProducto');
      window.toast && window.toast('Producto agregado', j.data.producto.nombre, { icon: 'OK' });
      cargarDetalle(ordenActual.id);
    } catch (e) { window.toast && window.toast('Error', e.message, { icon: '!' }); }
  });

  $('btnBuscar') && $('btnBuscar').addEventListener('click', listar);
  $('btnRefrescar') && $('btnRefrescar').addEventListener('click', listar);
  $('fQ') && $('fQ').addEventListener('keydown', function (e) { if (e.key === 'Enter') listar(); });

  // ── Eliminar órdenes vencidas (viejas y nunca cerradas) ─────────
  $('btnEliminarVencidas') && $('btnEliminarVencidas').addEventListener('click', async function () {
    var diasStr = window.prompt('¿Órdenes de más de cuántos días sin cerrar quieres eliminar?', '3');
    if (diasStr === null) return; // canceló
    var dias = parseInt(diasStr, 10);
    if (!dias || dias < 1) { window.toast && window.toast('Valor inválido', 'Escribe un número de días mayor a 0.', { icon: '!' }); return; }

    try {
      // 1) Preview: qué se va a borrar
      var r = await fetch(API + '/eliminar_vencidas.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dias: dias, confirm: false }),
      });
      var j = await r.json();
      if (!j.ok) throw new Error(j.detail || j.error);

      if (!j.count) {
        window.toast && window.toast('Nada que eliminar', 'No hay órdenes vencidas con ese criterio.', { icon: 'OK' });
        return;
      }

      var resumen = j.data.slice(0, 8).map(function (o) { return '• ' + o.folio + ' — ' + esc(o.cliente_nombre); }).join('\n');
      var extra = j.count > 8 ? '\n… y ' + (j.count - 8) + ' más.' : '';
      var confirmado = window.confirm(
        'Se eliminarán ' + j.count + ' orden(es) de más de ' + dias + ' día(s) sin cerrar:\n\n' + resumen + extra +
        '\n\nEsta acción no se puede deshacer. ¿Continuar?'
      );
      if (!confirmado) return;

      // 2) Confirmar borrado real
      var r2 = await fetch(API + '/eliminar_vencidas.php', {
        method: 'POST', headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ dias: dias, confirm: true }),
      });
      var j2 = await r2.json();
      if (!j2.ok) throw new Error(j2.detail || j2.error);

      window.toast && window.toast('Listo', j2.count + ' orden(es) eliminada(s).', { icon: 'OK' });
      listar();
    } catch (e) {
      window.toast && window.toast('Error', e.message, { icon: '!' });
    }
  });

  // ── Resumen / gráficas ──────────────────────────
  var chartEstados = null;
  var ESTADO_COLOR = {
    recibido: '#ffbe46',
    en_proceso: '#3c8cff',
    terminado: '#3cb478',
    entregado: 'rgba(255,255,255,.25)',
    cancelado: '#ff5a5a',
  };

  async function cargarResumen() {
    try {
      var r = await fetch(API + '/listar.php?tipo=taller&limit=200');
      var j = await r.json();
      if (!j.ok) throw new Error(j.error);
      var rows = j.data || [];

      var counts = { recibido: 0, en_proceso: 0, terminado: 0, entregado: 0, cancelado: 0 };
      var enTaller = 0, sinPagar = 0, esperandoEntrega = 0, extrasPend = 0;
      rows.forEach(function (o) {
        if (counts[o.estado] !== undefined) counts[o.estado]++;
        if (o.estado === 'en_proceso' || o.estado === 'recibido') enTaller++;
        if (!o.pagado && o.estado !== 'cancelado') sinPagar++;
        if (o.estado === 'terminado') esperandoEntrega++;
        extrasPend += Number(o.extras_pendientes || 0);
      });

      $('kpiEnTaller').textContent = enTaller;
      $('kpiSinPagar').textContent = sinPagar;
      $('kpiEsperandoEntrega').textContent = esperandoEntrega;
      $('kpiExtrasPend').textContent = extrasPend;

      var labels = Object.keys(counts).filter(function (k) { return counts[k] > 0; });
      var data = labels.map(function (k) { return counts[k]; });
      var colors = labels.map(function (k) { return ESTADO_COLOR[k]; });

      var leyenda = $('leyendaEstados');
      if (leyenda) {
        leyenda.innerHTML = labels.length ? labels.map(function (k) {
          return '<div style="display:flex;align-items:center;gap:6px"><span style="width:9px;height:9px;border-radius:50%;background:' + ESTADO_COLOR[k] + ';display:inline-block"></span>' +
            '<span class="muted">' + (ESTADO_LABEL[k] || k) + ':</span> <b>' + counts[k] + '</b></div>';
        }).join('') : '<div class="muted">Sin órdenes activas.</div>';
      }

      var canvas = $('chartEstados');
      if (canvas && window.Chart) {
        if (chartEstados) chartEstados.destroy();
        chartEstados = new Chart(canvas, {
          type: 'doughnut',
          data: {
            labels: labels.map(function (k) { return ESTADO_LABEL[k] || k; }),
            datasets: [{ data: data, backgroundColor: colors, borderWidth: 0 }],
          },
          options: {
            plugins: { legend: { display: false }, tooltip: { enabled: true } },
            cutout: '65%',
          },
        });
      }
    } catch (e) { /* silencioso: es un panel informativo, no bloquea el resto */ }
  }

  var _listarOriginal = listar;
  listar = function () { _listarOriginal(); cargarResumen(); };

  listar();
})();