<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard_movil.php';
require_once __DIR__ . '/../includes/functions.php';

// Este módulo es exclusivo del rol Mecánico. Un Lavador que llegue aquí
// se manda a su propio módulo.
if (operario_modulo() !== 'taller') {
  header('Location: ' . (operario_modulo() === 'autolavado' ? 'autolavado.php' : '../dashboard.php'));
  exit;
}

$nombre = htmlspecialchars((string)($_SESSION['user']['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$tema = current_tema();
?>
<!doctype html>
<html lang="es" data-tema="<?= htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Taller · BRAW MOTORS</title>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#a52727">
<link rel="apple-touch-icon" href="icons/icon-192.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="stylesheet" href="movil.css?v=5">
<style>
.det-wrap { display:none; margin-top:10px; border-top:1px solid var(--border); padding-top:10px; }
.det-wrap.open { display:block; }
.det-problema { font-size:12px; color:var(--muted); margin-bottom:10px; white-space:pre-wrap; }
.det-srv-list { display:flex; flex-direction:column; gap:6px; margin-bottom:10px; }
.det-srv-item { background:rgba(0,0,0,.18); border:1px solid var(--border); border-radius:10px; padding:8px 10px; font-size:12px; }
.det-toggle { width:100%; border:1px solid var(--border); background:rgba(255,255,255,.04); color:var(--muted); border-radius:10px; padding:8px; font-size:12px; margin-top:8px; }
.det-add { display:flex; gap:8px; margin-top:6px; }
.det-add select { flex:1; }
.det-extras { margin-top:14px; padding-top:10px; border-top:1px dashed var(--border); }
.det-extras h4 { margin:0 0 8px; font-size:12px; text-transform:uppercase; letter-spacing:.5px; color:var(--muted); }
.det-extra-item { background:rgba(0,0,0,.18); border:1px solid var(--border); border-radius:10px; padding:8px 10px; font-size:12px; margin-bottom:6px; }
.det-extra-item__top { display:flex; justify-content:space-between; align-items:center; gap:8px; }
.det-extra-item__monto { font-weight:800; }
.det-extra-item__just { color:var(--muted); margin-top:3px; }
.ext-pill { font-size:10px; border-radius:999px; padding:2px 8px; border:1px solid; white-space:nowrap; }
.ext-pill.pendiente { border-color:rgba(255,190,70,.4); background:rgba(255,190,70,.1); color:#ffbe46; }
.ext-pill.aprobado { border-color:rgba(60,180,120,.4); background:rgba(60,180,120,.1); color:#3cb478; }
.ext-pill.rechazado { border-color:rgba(255,90,90,.4); background:rgba(255,90,90,.1); color:#ff5a5a; }
.det-extra-form { margin-top:8px; }
.det-extra-form .field { margin-bottom:8px; }
.pago-pill { display:inline-block; font-size:11px; font-weight:800; border-radius:999px; padding:4px 10px; margin-top:6px; }
.pago-pill.pagado { background:rgba(60,180,120,.15); color:#3cb478; border:1px solid rgba(60,180,120,.4); }
.pago-pill.sin-pagar { background:rgba(255,190,70,.15); color:#ffbe46; border:1px solid rgba(255,190,70,.4); }
.extra-pill { display:inline-block; font-size:11px; font-weight:800; border-radius:999px; padding:4px 10px; margin-top:6px; margin-left:6px; background:rgba(213,94,79,.15); color:#d55e4f; border:1px solid rgba(213,94,79,.4); }
[data-tema="alto_contraste"] .det-srv-item,
[data-tema="alto_contraste"] .det-extra-item { background: rgba(15,17,25,.05); }
[data-tema="alto_contraste"] .det-toggle { background: #ffffff; }
[data-tema="alto_contraste"] .ext-pill.pendiente,
[data-tema="alto_contraste"] .pago-pill.sin-pagar { color:#8a5800; }
[data-tema="alto_contraste"] .ext-pill.aprobado,
[data-tema="alto_contraste"] .pago-pill.pagado { color:#1f7a4d; }
[data-tema="alto_contraste"] .ext-pill.rechazado { color:#b93a3a; }
[data-tema="alto_contraste"] .extra-pill { color:#a3402f; }
</style>
</head>
<body>
  <header class="topbar">
    <div class="topbar__brand">
      <b>🔧 Taller</b>
      <span>Pedidos, sin dinero</span>
    </div>
    <div class="topbar__user">
      <a class="btn-logout" href="agenda.php" title="Ver agenda de citas">📅 Agenda</a>
      <button class="tema-switch" type="button" id="btnTemaSwitch" title="Cambiar a modo alto contraste (para exteriores)">
        <span id="temaSwitchIcon"><?= $tema === 'alto_contraste' ? '☀️' : '🌙' ?></span>
      </button>
      <span><?= $nombre ?></span>
      <a class="btn-logout" href="logout.php">Salir</a>
    </div>
  </header>

  <div class="wrap">

    <!-- Nuevo pedido -->
    <div class="card">
      <h2>Cliente</h2>
      <div class="ac-drop">
        <input class="input" id="cliBuscar" type="search" placeholder="Buscar nombre o teléfono…" autocomplete="off">
        <div class="ac-list" id="cliDrop"></div>
      </div>
      <div class="chip" id="cliChip">
        <div class="chip__info">
          <div class="chip__name" id="chipNombre">—</div>
          <div class="chip__sub" id="chipTel"></div>
        </div>
        <button class="chip__clear" id="btnCliClear" type="button">✕</button>
      </div>
      <button class="btn btn--ghost" id="btnToggleNuevoCli" type="button" style="margin-top:10px">+ Nuevo cliente</button>

      <div id="formNuevoCli" style="display:none;margin-top:12px">
        <div class="field" style="margin-bottom:10px">
          <label>Nombre completo</label>
          <input class="input" id="ncNombre" type="text" placeholder="Nombre Apellido">
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Teléfono</label>
          <input class="input" id="ncTel" type="tel" placeholder="3411234567">
        </div>
        <button class="btn" id="btnGuardarCliente" type="button">Guardar cliente</button>
      </div>

      <div id="secVehiculo" style="display:none;margin-top:14px">
        <h2 style="margin-top:14px">Vehículo <span style="text-transform:none;font-weight:400">(opcional)</span></h2>
        <div class="vh-list" id="vhList"></div>
        <button class="btn btn--ghost" id="btnToggleNuevoVeh" type="button" style="margin-top:10px">+ Agregar vehículo</button>

        <div id="formNuevoVeh" style="display:none;margin-top:12px">
          <div class="field" style="margin-bottom:10px">
            <label>Marca</label>
            <input class="input" id="vhMarca" type="text" placeholder="Honda, Yamaha, Italika…">
          </div>
          <div class="field" style="margin-bottom:10px">
            <label>Modelo</label>
            <input class="input" id="vhModelo" type="text" placeholder="CBR, FZ…">
          </div>
          <div class="field" style="margin-bottom:10px">
            <label>Placas</label>
            <input class="input" id="vhPlacas" type="text" placeholder="ABC-123">
          </div>
          <button class="btn" id="btnGuardarVehiculo" type="button">Guardar vehículo</button>
        </div>
      </div>
    </div>

    <div class="card">
      <h2>Problema reportado</h2>
      <div class="field" style="margin-bottom:10px">
        <label>Descripción del problema</label>
        <textarea class="input" id="problema" rows="3" placeholder="Qué reporta el cliente…"></textarea>
      </div>
      <div class="field" style="margin-bottom:10px">
        <label>Kilometraje <span style="text-transform:none;font-weight:400">(opcional)</span></label>
        <input class="input" id="km" type="number" min="0" placeholder="Ej: 23540">
      </div>
      <div class="field">
        <label>Notas internas <span style="text-transform:none;font-weight:400">(opcional)</span></label>
        <textarea class="input" id="notas" rows="2" placeholder="Notas para el taller…"></textarea>
      </div>
    </div>

    <div class="card">
      <h2>Servicios <span style="text-transform:none;font-weight:400">(opcional, se pueden agregar después)</span></h2>
      <div class="srv-grid" id="srvGrid">
        <div class="empty" style="grid-column:1/-1">Cargando…</div>
      </div>
      <div id="srvSeleccion" style="margin-top:10px"></div>
      <button class="btn" id="btnEnviar" disabled type="button" style="margin-top:14px">Enviar pedido</button>
    </div>

    <!-- Pedidos activos -->
    <div class="card">
      <h2 style="display:flex;justify-content:space-between;align-items:center">
        <span>Pedidos activos</span>
        <button class="btn-logout" id="btnRefrescar" type="button" style="background:none">↻</button>
      </h2>
      <div id="activosList">
        <div class="empty">Cargando…</div>
      </div>
    </div>

  </div>

  <div class="toast" id="toast"><b id="toastTitle"></b><span id="toastMsg"></span></div>

<script>
(() => {
  const API = '../php/api';
  const $ = id => document.getElementById(id);

  const state = {
    servicios: [],
    seleccion: [],   // [{id, nombre}]  servicios elegidos al crear el pedido
    cliente: null,
    vehiculo: null,
    cliTimer: null,
    detalleAbierto: {}, // { [ordenId]: bool }
  };

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  function toast(title, msg) {
    $('toastTitle').textContent = title;
    $('toastMsg').textContent = msg || '';
    $('toast').classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => $('toast').classList.remove('show'), 3200);
  }

  // Toggle de modo alto contraste (para leer bajo sol directo)
  (() => {
    const btn = $('btnTemaSwitch');
    if (!btn) return;
    const icon = $('temaSwitchIcon');
    btn.addEventListener('click', async () => {
      const actual = document.documentElement.getAttribute('data-tema') === 'alto_contraste' ? 'alto_contraste' : 'oscuro';
      const nuevo = actual === 'alto_contraste' ? 'oscuro' : 'alto_contraste';
      document.documentElement.setAttribute('data-tema', nuevo);
      if (icon) icon.textContent = nuevo === 'alto_contraste' ? '☀️' : '🌙';
      try {
        const res = await fetch('../php/api/usuarios/tema.php', {
          method: 'POST',
          headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tema: nuevo }),
        });
        const j = await res.json();
        if (!j.ok) throw new Error(j.error || 'No se pudo guardar');
      } catch (e) {
        toast('Aviso', 'El tema cambió, pero no se pudo guardar tu preferencia.');
      }
    });
  })();

  function actualizarBtnEnviar() {
    const btn = $('btnEnviar');
    const listo = !!state.cliente && $('problema').value.trim() !== '';
    btn.disabled = !listo;
  }

  // ── Servicios de taller ────────────────────
  async function cargarServicios() {
    try {
      const r = await fetch(`${API}/servicios/list.php?tipo=taller`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      state.servicios = j.data || [];
      renderServicios();
    } catch (e) {
      $('srvGrid').innerHTML = `<div class="empty" style="grid-column:1/-1">Error: ${esc(e.message)}</div>`;
    }
  }

  function renderServicios() {
    const grid = $('srvGrid');
    if (!state.servicios.length) {
      grid.innerHTML = '<div class="empty" style="grid-column:1/-1">Sin servicios activos.</div>';
      return;
    }
    grid.innerHTML = state.servicios.map(s => {
      const sel = state.seleccion.find(x => x.id === s.id);
      return `
        <button class="srv-btn ${sel ? 'is-sel' : ''}" data-sid="${s.id}" type="button">
          <div class="srv-btn__badge">${sel ? '✓' : ''}</div>
          <div class="srv-btn__name">${esc(s.nombre)}</div>
        </button>`;
    }).join('');

    grid.querySelectorAll('.srv-btn').forEach(btn => {
      btn.addEventListener('click', () => {
        const id = Number(btn.dataset.sid);
        const srv = state.servicios.find(s => s.id === id);
        if (!srv) return;
        const idx = state.seleccion.findIndex(s => s.id === id);
        if (idx >= 0) state.seleccion.splice(idx, 1);
        else state.seleccion.push({ id: srv.id, nombre: srv.nombre });
        renderServicios();
        renderSeleccion();
      });
    });
  }

  function renderSeleccion() {
    const box = $('srvSeleccion');
    if (!state.seleccion.length) { box.innerHTML = ''; return; }
    box.innerHTML = state.seleccion.map((s, i) => `
      <div class="pedido-item">
        <span class="pedido-item__name">${esc(s.nombre)}</span>
        <button class="pedido-item__rm" data-rm="${i}" type="button">✕</button>
      </div>`).join('');
    box.querySelectorAll('[data-rm]').forEach(b => {
      b.addEventListener('click', () => {
        state.seleccion.splice(Number(b.dataset.rm), 1);
        renderServicios();
        renderSeleccion();
      });
    });
  }

  // ── Cliente ────────────────────────────────
  const inpCli = $('cliBuscar');
  const dropCli = $('cliDrop');

  inpCli.addEventListener('input', () => {
    clearTimeout(state.cliTimer);
    const q = inpCli.value.trim();
    if (q.length < 2) { dropCli.classList.remove('open'); return; }
    state.cliTimer = setTimeout(() => buscarClientes(q), 280);
  });

  async function buscarClientes(q) {
    try {
      const r = await fetch(`${API}/clientes/list.php?q=${encodeURIComponent(q)}`);
      const j = await r.json();
      const list = Array.isArray(j) ? j : (j.data || []);
      dropCli.innerHTML = list.length
        ? list.map(c => `<div class="ac-item" data-cid="${c.id}" data-nom="${esc(c.nombre_completo)}" data-tel="${esc(c.telefono)}"><strong>${esc(c.nombre_completo)}</strong><small>${esc(c.telefono)}</small></div>`).join('')
        : '<div class="ac-item" style="color:var(--muted)">Sin resultados</div>';
      dropCli.classList.add('open');
      dropCli.querySelectorAll('[data-cid]').forEach(item => {
        item.addEventListener('click', () => seleccionarCliente({
          id: Number(item.dataset.cid), nombre_completo: item.dataset.nom, telefono: item.dataset.tel,
        }));
      });
    } catch (e) {}
  }

  function seleccionarCliente(c) {
    state.cliente = c;
    state.vehiculo = null;
    dropCli.classList.remove('open');
    inpCli.value = '';
    $('cliChip').classList.add('show');
    $('chipNombre').textContent = c.nombre_completo;
    $('chipTel').textContent = c.telefono;
    $('secVehiculo').style.display = 'block';
    cargarVehiculos(c.id);
    actualizarBtnEnviar();
  }

  $('btnCliClear').addEventListener('click', () => {
    state.cliente = null;
    state.vehiculo = null;
    $('cliChip').classList.remove('show');
    $('secVehiculo').style.display = 'none';
    $('vhList').innerHTML = '';
    actualizarBtnEnviar();
  });

  document.addEventListener('click', e => {
    if (!e.target.closest('#cliBuscar') && !e.target.closest('#cliDrop')) {
      dropCli.classList.remove('open');
    }
  });

  $('btnToggleNuevoCli').addEventListener('click', () => {
    const f = $('formNuevoCli');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
  });

  $('btnGuardarCliente').addEventListener('click', async () => {
    const nombre = $('ncNombre').value.trim();
    const tel = $('ncTel').value.trim();
    if (!nombre || !tel) { toast('Faltan datos', 'Nombre y teléfono son requeridos.'); return; }
    try {
      const r = await fetch(`${API}/clientes/create.php`, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ nombre_completo: nombre, telefono: tel }),
      });
      const j = await r.json();
      if (j.error) throw new Error(j.error);
      $('formNuevoCli').style.display = 'none';
      $('ncNombre').value = ''; $('ncTel').value = '';
      seleccionarCliente(j);
      toast('Cliente registrado', nombre);
    } catch (e) { toast('Error', e.message); }
  });

  $('problema').addEventListener('input', actualizarBtnEnviar);

  // ── Vehículos ──────────────────────────────
  async function cargarVehiculos(clienteId) {
    const lista = $('vhList');
    lista.innerHTML = '<span style="color:var(--muted);font-size:12px">Cargando…</span>';
    try {
      const r = await fetch(`${API}/vehiculos/list.php?cliente_id=${clienteId}&activo=1`);
      const j = await r.json();
      const vhs = Array.isArray(j) ? j : (j.data || []);
      lista.innerHTML = vhs.length ? vhs.map(v => `
        <button class="vh-btn" data-vid="${v.id}" type="button">
          <div>${esc(v.marca)} ${esc(v.modelo||'')}</div>
          <div style="color:var(--muted);font-size:11px">${esc(v.placas||v.placa||'Sin placas')}</div>
        </button>`).join('') : '<div class="empty">Sin vehículos registrados.</div>';

      lista.querySelectorAll('.vh-btn').forEach(btn => {
        btn.addEventListener('click', () => {
          lista.querySelectorAll('.vh-btn').forEach(b => b.classList.remove('is-sel'));
          btn.classList.add('is-sel');
          state.vehiculo = { id: Number(btn.dataset.vid) };
        });
      });
    } catch (e) {
      lista.innerHTML = '<div class="empty">Error al cargar.</div>';
    }
  }

  $('btnToggleNuevoVeh').addEventListener('click', () => {
    const f = $('formNuevoVeh');
    f.style.display = f.style.display === 'none' ? 'block' : 'none';
  });

  $('btnGuardarVehiculo').addEventListener('click', async () => {
    if (!state.cliente) return;
    const marca = $('vhMarca').value.trim();
    if (!marca) { toast('Falta marca', ''); return; }
    try {
      const r = await fetch(`${API}/vehiculos/create.php`, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          cliente_id: state.cliente.id, marca,
          modelo: $('vhModelo').value || '', placas: $('vhPlacas').value || '',
        }),
      });
      const j = await r.json();
      if (j.error) throw new Error(j.error);
      $('formNuevoVeh').style.display = 'none';
      $('vhMarca').value=''; $('vhModelo').value=''; $('vhPlacas').value='';
      cargarVehiculos(state.cliente.id);
      toast('Vehículo registrado', marca);
    } catch (e) { toast('Error', e.message); }
  });

  // ── Enviar pedido ──────────────────────────
  $('btnEnviar').addEventListener('click', async () => {
    if (!state.cliente || !$('problema').value.trim()) return;
    const btn = $('btnEnviar');
    btn.disabled = true;
    btn.textContent = 'Enviando…';
    try {
      const r = await fetch(`${API}/ordenes/crear.php`, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({
          tipo: 'taller',
          cliente_id: state.cliente.id,
          vehiculo_id: state.vehiculo?.id || null,
          descripcion_problema: $('problema').value.trim(),
          kilometraje_entrada: $('km').value || null,
          notas: $('notas').value || '',
        }),
      });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'Error al enviar pedido');
      const ordenId = j.data.id;

      for (const srv of state.seleccion) {
        const rs = await fetch(`${API}/ordenes/agregar_servicio.php`, {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({ orden_id: ordenId, servicio_id: srv.id, cantidad: 1 }),
        });
        const js = await rs.json();
        if (!js.ok) throw new Error(js.error || 'Error al agregar servicio');
      }

      toast('Pedido enviado', `Folio ${j.data.folio} — ya está en la PC principal.`);

      // reset
      state.cliente = null; state.vehiculo = null; state.seleccion = [];
      $('cliChip').classList.remove('show');
      $('secVehiculo').style.display = 'none';
      $('problema').value = ''; $('km').value = ''; $('notas').value = '';
      renderServicios();
      renderSeleccion();
      actualizarBtnEnviar();
      cargarActivos();
    } catch (e) {
      toast('Error', e.message);
    } finally {
      btn.textContent = 'Enviar pedido';
      actualizarBtnEnviar();
    }
  });

  // ── Pedidos activos ────────────────────────
  async function cargarActivos() {
    const list = $('activosList');
    try {
      const r = await fetch(`${API}/ordenes/listar.php?tipo=taller`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      const activos = (j.data || []).filter(o => o.estado !== 'entregado' && o.estado !== 'cancelado');
      if (!activos.length) {
        list.innerHTML = '<div class="empty">Sin pedidos activos.</div>';
        return;
      }
      list.innerHTML = activos.map(o => {
        const abierto = !!state.detalleAbierto[o.id];
        return `
        <div class="orden-card ${o.estado === 'recibido' ? 'pendiente' : o.estado}">
          <div class="orden-card__head">
            <span class="orden-card__id">${esc(o.folio)}</span>
            <span class="e-pill ${o.estado === 'recibido' ? 'pendiente' : o.estado}">${o.estado.replace('_',' ')}</span>
          </div>
          <div class="orden-card__srv">${esc(o.cliente_nombre)}</div>
          <div class="orden-card__cli">${esc(o.vehiculo_desc || 'Sin vehículo')}</div>
          <div class="pago-pill ${o.pagado ? 'pagado' : 'sin-pagar'}">${o.pagado ? '✓ Pagado' : '⏳ Sin pagar'}</div>
          ${o.extras_pendientes > 0 ? `<div class="extra-pill">⚡ ${o.extras_pendientes} extra${o.extras_pendientes>1?'s':''} en revisión</div>` : ''}
          ${o.estado === 'recibido'   ? `<button class="orden-card__btn iniciar" data-accion="en_proceso" data-oid="${o.id}" type="button">▶ Iniciar</button>` : ''}
          ${o.estado === 'en_proceso' ? `<button class="orden-card__btn terminar" data-accion="terminado" data-oid="${o.id}" type="button">✓ Terminar</button>` : ''}
          ${o.estado === 'terminado'  ? `<div class="orden-card__esperando">Esperando entrega</div>` : ''}
          <button class="det-toggle" data-detoggle="${o.id}" type="button">${abierto ? '▲ Ocultar detalle' : '▼ Ver / agregar servicios / pedir extra'}</button>
          <div class="det-wrap ${abierto ? 'open' : ''}" id="det-${o.id}"></div>
        </div>`;
      }).join('');

      list.querySelectorAll('[data-accion]').forEach(btn => {
        btn.addEventListener('click', async () => {
          try {
            const r = await fetch(`${API}/ordenes/cambiar_estado.php`, {
              method: 'POST', headers: {'Content-Type':'application/json'},
              body: JSON.stringify({ id: Number(btn.dataset.oid), estado: btn.dataset.accion }),
            });
            const j = await r.json();
            if (!j.ok) throw new Error(j.error);
            cargarActivos();
          } catch (e) { toast('Error', e.message); }
        });
      });

      list.querySelectorAll('[data-detoggle]').forEach(btn => {
        btn.addEventListener('click', () => {
          const oid = Number(btn.dataset.detoggle);
          state.detalleAbierto[oid] = !state.detalleAbierto[oid];
          if (state.detalleAbierto[oid]) cargarDetalle(oid);
          else cargarActivos();
        });
      });

      // Si un panel de detalle quedó marcado como abierto (p.ej. el refresco
      // automático de 20s reconstruyó la lista), su contenido se recrea vacío.
      // Lo repoblamos para no dejar al usuario con un panel en blanco.
      Object.keys(state.detalleAbierto).forEach(oid => {
        if (state.detalleAbierto[oid] && activos.some(o => o.id === Number(oid))) {
          cargarDetalle(Number(oid));
        }
      });
    } catch (e) {
      list.innerHTML = `<div class="empty">Error: ${esc(e.message)}</div>`;
    }
  }

  async function cargarDetalle(ordenId) {
    const wrap = $('det-' + ordenId);
    if (!wrap) return;
    wrap.classList.add('open');
    wrap.innerHTML = '<div class="empty">Cargando…</div>';
    try {
      const r = await fetch(`${API}/ordenes/detalle.php?id=${ordenId}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      const { orden, servicios, extras } = j.data;

      const srvHtml = servicios.length
        ? servicios.map(s => `<div class="det-srv-item">${esc(s.servicio_nombre)} × ${s.cantidad}</div>`).join('')
        : '<div class="empty">Sin servicios agregados aún.</div>';

      const ESTADO_EXT = { pendiente: 'En revisión', aprobado: 'Aprobado', rechazado: 'Rechazado' };
      const extrasList = (extras || []);
      const extrasHtml = extrasList.length
        ? extrasList.map(ex => `
          <div class="det-extra-item">
            <div class="det-extra-item__top">
              <span class="det-extra-item__monto">$${Number(ex.monto_sugerido).toFixed(2)} sugerido</span>
              <span class="ext-pill ${ex.estado}">${ESTADO_EXT[ex.estado] || ex.estado}</span>
            </div>
            <div class="det-extra-item__just">${esc(ex.justificacion)}</div>
          </div>`).join('')
        : '<div class="empty">Sin extras agregados.</div>';

      wrap.innerHTML = `
        <div class="det-problema"><b>Problema:</b> ${esc(orden.descripcion_problema || '—')}</div>
        <div class="det-srv-list">${srvHtml}</div>
        <div class="det-add">
          <select class="input" id="addSrv-${ordenId}">
            <option value="">+ Agregar servicio…</option>
            ${state.servicios.map(s => `<option value="${s.id}">${esc(s.nombre)}</option>`).join('')}
          </select>
          <button class="btn" style="width:auto;padding:0 16px" data-addsrv="${ordenId}" type="button">Agregar</button>
        </div>

        <div class="det-extras">
          <h4>Extra por dificultad (ej. moto grande/sucia)</h4>
          <div>${extrasHtml}</div>
          <div class="det-extra-form">
            <div class="field">
              <label>Monto sugerido</label>
              <input class="input" type="number" min="1" step="0.01" placeholder="Ej: 150" id="extMonto-${ordenId}">
            </div>
            <div class="field">
              <label>Razón (ej. modelo de moto, trabajo extra)</label>
              <textarea class="input" rows="2" placeholder="Ej: CBR600RR, más difícil que una Italika AT110" id="extJust-${ordenId}"></textarea>
            </div>
            <button class="btn btn--ghost" style="width:100%" data-addext="${ordenId}" type="button">+ Enviar extra a revisión</button>
          </div>
        </div>`;

      wrap.querySelector(`[data-addsrv="${ordenId}"]`).addEventListener('click', async () => {
        const sel = $('addSrv-' + ordenId);
        const servicioId = Number(sel.value);
        if (!servicioId) return;
        try {
          const rs = await fetch(`${API}/ordenes/agregar_servicio.php`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ orden_id: ordenId, servicio_id: servicioId, cantidad: 1 }),
          });
          const js = await rs.json();
          if (!js.ok) throw new Error(js.error);
          toast('Servicio agregado', '');
          cargarDetalle(ordenId);
        } catch (e) { toast('Error', e.message); }
      });

      wrap.querySelector(`[data-addext="${ordenId}"]`).addEventListener('click', async () => {
        const btnExt = wrap.querySelector(`[data-addext="${ordenId}"]`);
        const monto = Number($('extMonto-' + ordenId).value);
        const just = $('extJust-' + ordenId).value.trim();
        if (!monto || monto <= 0) { toast('Falta el monto', 'Escribe un monto sugerido mayor a 0.'); return; }
        if (!just) { toast('Falta la razón', 'Explica por qué se necesita el extra.'); return; }
        btnExt.disabled = true;
        try {
          const re = await fetch(`${API}/ordenes/extras/crear.php`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ orden_id: ordenId, monto_sugerido: monto, justificacion: just }),
          });
          const je = await re.json();
          if (!je.ok) throw new Error(je.error);
          toast('Extra enviado', 'Caja lo revisará antes de cobrar.');
          cargarDetalle(ordenId);
          cargarActivos();
        } catch (e) {
          toast('Error', e.message);
          btnExt.disabled = false;
        }
      });
    } catch (e) {
      wrap.innerHTML = `<div class="empty">Error: ${esc(e.message)}</div>`;
    }
  }

  $('btnRefrescar').addEventListener('click', cargarActivos);
  setInterval(cargarActivos, 20000);

  cargarServicios();
  cargarActivos();
})();

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').catch(()=>{});
}
</script>
</body>
</html>
