<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard_movil.php';
require_once __DIR__ . '/../includes/functions.php';

// Este módulo es exclusivo del rol Lavador. Un Mecánico que llegue aquí
// (URL directa, favorito viejo, etc.) se manda a su propio módulo.
if (operario_modulo() !== 'autolavado') {
  header('Location: ' . (operario_modulo() === 'taller' ? 'taller.php' : '../dashboard.php'));
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
<title>Autolavado · BRAW MOTORS</title>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#a52727">
<link rel="apple-touch-icon" href="icons/icon-192.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="stylesheet" href="movil.css?v=5">
<style>
.det-wrap { display:none; margin-top:10px; border-top:1px solid var(--border); padding-top:10px; }
.det-wrap.open { display:block; }
.det-toggle { width:100%; border:1px solid var(--border); background:rgba(255,255,255,.04); color:var(--muted); border-radius:10px; padding:8px; font-size:12px; margin-top:8px; }
.det-extras { margin-top:14px; padding-top:10px; border-top:1px dashed var(--border); }
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
      <a href="index.php" style="text-decoration:none;color:inherit">
        <b>← Autolavado</b>
      </a>
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
      <h2>Servicios</h2>
      <div class="srv-grid" id="srvGrid">
        <div class="empty" style="grid-column:1/-1">Cargando…</div>
      </div>

      <button class="extras-toggle" id="btnToggleExtras" type="button" style="display:none;">
        <span id="extrasToggleLabel">+ Agregar extra</span>
      </button>
      <div class="srv-grid" id="srvGridExtras" style="display:none; margin-top:10px;"></div>

      <div class="rango-picker" id="rangoPicker" style="display:none"></div>
    </div>

    <div class="card">
      <h2>Cliente <span style="text-transform:none;font-weight:400">(opcional)</span></h2>
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
        <h2 style="margin-top:14px">Vehículo</h2>
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
      <h2>Pedido</h2>
      <div id="pedidoLista">
        <div class="empty">Selecciona servicios arriba</div>
      </div>
      <div class="field" style="margin-top:10px">
        <label>Notas</label>
        <textarea class="input" id="notas" rows="2" placeholder="Observaciones del servicio…"></textarea>
      </div>
      <button class="btn" id="btnEnviar" disabled type="button" style="margin-top:14px">Enviar pedido</button>
    </div>

    <!-- Pedidos activos -->
    <div class="card">
      <h2 style="display:flex;justify-content:space-between;align-items:center">
        <span>Pedidos activos hoy</span>
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
    seleccion: [],   // [{id, nombre, precio?}] — precio solo si viene de un rango sugerido
    cliente: null,
    vehiculo: null,
    cliTimer: null,
    detalleAbierto: {}, // { [ordenId]: bool }
    grupoExpandido: null, // nombre del paquete con el sub-selector de tamaño abierto
    extrasAbierto: false,
    rangoServicio: null,  // servicio con selector de precio abierto
  };

  // Paquetes principales: se muestran como botón grande con sub-selector de
  // tamaño, igual que en la PC principal. Todo lo demás (Lavado Interior,
  // Full Detallado Interior, y servicios sueltos) se agrupa bajo
  // "+ Agregar extra" para no saturar la pantalla del celular.
  const PRINCIPAL_GROUPS = ['Detallado Spa', 'Detallado Braw', 'Detallado Luxury'];
  // Servicios sueltos (sin variantes de tamaño) que también se muestran
  // como botón principal en vez de esconderse en "+ Agregar extra".
  const PRINCIPAL_SUELTOS = ['Lavado de Moto', 'Detallado de Moto', 'Detallado Premium de Moto'];

  function esc(s) {
    return String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  }

  // Agrupa servicios cuyo nombre sigue el patrón "Grupo - Talla"; el resto
  // queda como extra suelto.
  function agruparServicios(lista) {
    const grupos = {};
    const sueltos = [];
    lista.forEach(s => {
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

  function tieneRango(s) {
    return s.precio_min !== null && s.precio_min !== undefined
      && s.precio_max !== null && s.precio_max !== undefined;
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

  // ── Servicios ──────────────────────────────
  async function cargarServicios() {
    try {
      const r = await fetch(`${API}/servicios/list.php?tipo=autolavado`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      state.servicios = j.data || [];
      renderServicios();
    } catch (e) {
      $('srvGrid').innerHTML = `<div class="empty" style="grid-column:1/-1">Error: ${esc(e.message)}</div>`;
    }
  }

  function grupoCardHTML(grupo, tallas) {
    const expandido = state.grupoExpandido === grupo;
    const enSel = tallas.some(t => state.seleccion.some(x => x.id === t.servicio.id));

    if (expandido) {
      return `
        <div class="srv-btn is-sel" style="grid-column:1/-1">
          <div class="srv-btn__name">${esc(grupo)}</div>
          <div class="srv-btn__hint">Elige el tamaño:</div>
          <div class="srv-btn__tallas">
            ${tallas.map(t => `
              <button class="talla-btn" data-sid="${t.servicio.id}" type="button">
                ${esc(t.talla)}${tieneRango(t.servicio) ? ' 💲' : ''}
              </button>`).join('')}
          </div>
        </div>`;
    }

    return `
      <button class="srv-btn ${enSel ? 'is-sel' : ''}" data-grupoabrir="${esc(grupo)}" type="button">
        <div class="srv-btn__badge">${enSel ? '✓' : ''}</div>
        <div class="srv-btn__name">${esc(grupo)}</div>
        <div class="srv-btn__hint">Toca para elegir tamaño</div>
      </button>`;
  }

  function sueltoCardHTML(s) {
    const sel = state.seleccion.find(x => x.id === s.id);
    return `
      <button class="srv-btn ${sel ? 'is-sel' : ''}" data-sid="${s.id}" type="button">
        <div class="srv-btn__badge">${sel ? '✓' : ''}</div>
        <div class="srv-btn__name">${esc(s.nombre)}</div>
        ${s.tiempo_estimado ? `<div class="srv-btn__tiempo">~${s.tiempo_estimado} min</div>` : ''}
        ${tieneRango(s) ? `<div class="srv-btn__hint">💲 Toca para elegir precio</div>` : ''}
      </button>`;
  }

  function renderServicios() {
    const grid = $('srvGrid');
    const gridExtras = $('srvGridExtras');
    const btnToggle = $('btnToggleExtras');
    if (!state.servicios.length) {
      grid.innerHTML = '<div class="empty" style="grid-column:1/-1">Sin servicios activos.</div>';
      if (btnToggle) btnToggle.style.display = 'none';
      return;
    }

    const { grupos, sueltos } = agruparServicios(state.servicios);
    const sueltosPrincipales = sueltos.filter(s => PRINCIPAL_SUELTOS.includes(s.nombre));
    const sueltosExtra = sueltos.filter(s => !PRINCIPAL_SUELTOS.includes(s.nombre));

    // Principales: los 3 paquetes, en orden fijo, más los sueltos
    // promovidos a principal (ej. Lavado de Motor).
    grid.innerHTML = PRINCIPAL_GROUPS
      .filter(g => grupos[g])
      .map(g => grupoCardHTML(g, grupos[g]))
      .join('') + sueltosPrincipales.map(sueltoCardHTML).join('');

    // Extras: el resto de grupos por tamaño (Lavado Interior, Full
    // Detallado Interior…) más los demás servicios sueltos.
    const extraGrupos = Object.keys(grupos).filter(g => !PRINCIPAL_GROUPS.includes(g));
    const extrasHTML = extraGrupos.map(g => grupoCardHTML(g, grupos[g])).join('')
      + sueltosExtra.map(sueltoCardHTML).join('');

    if (gridExtras) gridExtras.innerHTML = extrasHTML;
    if (btnToggle) btnToggle.style.display = extrasHTML ? '' : 'none';
    if (gridExtras) gridExtras.style.display = state.extrasAbierto ? '' : 'none';
    const lbl = $('extrasToggleLabel');
    if (lbl) lbl.textContent = state.extrasAbierto ? '– Ocultar extras' : '+ Agregar extra';

    [grid, gridExtras].forEach(g => {
      if (!g) return;
      g.querySelectorAll('[data-grupoabrir]').forEach(btn => {
        btn.addEventListener('click', () => {
          state.grupoExpandido = btn.dataset.grupoabrir;
          renderServicios();
        });
      });
      g.querySelectorAll('[data-sid]').forEach(btn => {
        btn.addEventListener('click', () => {
          seleccionarServicio(Number(btn.dataset.sid));
        });
      });
    });
  }

  $('btnToggleExtras').addEventListener('click', () => {
    state.extrasAbierto = !state.extrasAbierto;
    renderServicios();
  });

  function seleccionarServicio(id) {
    const srv = state.servicios.find(s => s.id === id);
    if (!srv) return;

    const idx = state.seleccion.findIndex(s => s.id === id);
    if (idx >= 0) {
      // Ya estaba elegido: quitarlo.
      state.seleccion.splice(idx, 1);
      state.grupoExpandido = null;
      renderServicios();
      renderPedido();
      return;
    }

    if (tieneRango(srv)) {
      abrirRangoPicker(srv);
      return;
    }

    state.seleccion.push({ id: srv.id, nombre: srv.nombre });
    state.grupoExpandido = null;
    renderServicios();
    renderPedido();
  }

  // ── Selector de precio (servicios con rango sugerido) — barra tipo
  // volumen: el lavador arrastra entre el mínimo y el máximo del
  // servicio, ve el monto en vivo, y confirma con un botón. ──
  function abrirRangoPicker(srv) {
    state.rangoServicio = srv;
    const wrap = $('rangoPicker');
    const min = Number(srv.precio_min);
    const max = Number(srv.precio_max);
    const inicial = Math.round((min + max) / 2 / 10) * 10;

    wrap.style.display = 'block';
    wrap.innerHTML = `
      <div class="rango-picker__title">¿Cuánto cobrar por "${esc(srv.nombre)}"?</div>
      <div class="rango-slider__value" id="rangoSliderValor">$${inicial}</div>
      <div class="rango-slider__track-wrap">
        <span class="rango-slider__min">$${min.toFixed(0)}</span>
        <input type="range" id="rangoSliderInput" class="rango-slider" min="${min}" max="${max}" step="10" value="${inicial}">
        <span class="rango-slider__max">$${max.toFixed(0)}</span>
      </div>
      <button class="btn rango-picker__confirm" type="button" id="btnConfirmarRango">Usar $${inicial}</button>
      <button class="rango-picker__cancel" type="button" id="btnCancelarRango">Cancelar</button>
    `;

    const inp = $('rangoSliderInput');
    const valorEl = $('rangoSliderValor');
    const btnConfirmar = $('btnConfirmarRango');

    function actualizarSlider() {
      const v = Number(inp.value);
      const pct = ((v - min) / (max - min)) * 100;
      inp.style.background = `linear-gradient(to right, var(--primary2) 0%, var(--primary2) ${pct}%, rgba(255,255,255,.15) ${pct}%, rgba(255,255,255,.15) 100%)`;
      valorEl.textContent = '$' + v.toFixed(0);
      btnConfirmar.textContent = 'Usar $' + v.toFixed(0);
    }
    actualizarSlider();
    inp.addEventListener('input', actualizarSlider);

    btnConfirmar.addEventListener('click', () => {
      const val = Number(inp.value);
      state.seleccion.push({ id: srv.id, nombre: srv.nombre, precio: val });
      cerrarRangoPicker();
      state.grupoExpandido = null;
      renderServicios();
      renderPedido();
    });
    $('btnCancelarRango').addEventListener('click', cerrarRangoPicker);
    wrap.scrollIntoView({ behavior: 'smooth', block: 'center' });
  }

  function cerrarRangoPicker() {
    state.rangoServicio = null;
    const wrap = $('rangoPicker');
    wrap.style.display = 'none';
    wrap.innerHTML = '';
  }

  function renderPedido() {
    const lista = $('pedidoLista');
    const btn = $('btnEnviar');
    if (!state.seleccion.length) {
      lista.innerHTML = '<div class="empty">Selecciona servicios arriba</div>';
      btn.disabled = true;
      return;
    }
    lista.innerHTML = state.seleccion.map((s, i) => `
      <div class="pedido-item">
        <span class="pedido-item__name">${esc(s.nombre)}${s.precio != null ? ` · $${Number(s.precio).toFixed(0)}` : ''}</span>
        <button class="pedido-item__rm" data-rm="${i}" type="button">✕</button>
      </div>`).join('');
    lista.querySelectorAll('[data-rm]').forEach(b => {
      b.addEventListener('click', () => {
        state.seleccion.splice(Number(b.dataset.rm), 1);
        renderServicios();
        renderPedido();
      });
    });
    btn.disabled = false;
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
  }

  $('btnCliClear').addEventListener('click', () => {
    state.cliente = null;
    state.vehiculo = null;
    $('cliChip').classList.remove('show');
    $('secVehiculo').style.display = 'none';
    $('vhList').innerHTML = '';
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
    if (!state.seleccion.length) return;
    const btn = $('btnEnviar');
    btn.disabled = true;
    btn.textContent = 'Enviando…';
    try {
      for (const srv of state.seleccion) {
        const r = await fetch(`${API}/autolavado/create.php`, {
          method: 'POST', headers: {'Content-Type':'application/json'},
          body: JSON.stringify({
            servicio_id: srv.id,
            cliente_id: state.cliente?.id || null,
            vehiculo_id: state.vehiculo?.id || null,
            notas: $('notas').value || '',
            ...(srv.precio != null ? { precio: srv.precio } : {}),
          }),
        });
        const j = await r.json();
        if (!j.ok) throw new Error(j.error || 'Error al enviar pedido');
      }
      toast('Pedido enviado', 'Ya está en la PC principal.');
      state.seleccion = [];
      $('notas').value = '';
      renderServicios();
      renderPedido();
      cargarActivos();
    } catch (e) {
      toast('Error', e.message);
    } finally {
      btn.textContent = 'Enviar pedido';
      btn.disabled = state.seleccion.length === 0;
    }
  });

  // ── Pedidos activos ────────────────────────
  async function cargarActivos() {
    const list = $('activosList');
    try {
      const r = await fetch(`${API}/autolavado/list.php`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      const activos = (j.data || []).filter(o => o.estado !== 'entregado');
      if (!activos.length) {
        list.innerHTML = '<div class="empty">Sin pedidos activos hoy.</div>';
        return;
      }
      list.innerHTML = activos.map(o => {
        const abierto = !!state.detalleAbierto[o.id];
        return `
        <div class="orden-card ${o.estado}">
          <div class="orden-card__head">
            <span class="orden-card__id">#${o.id}</span>
            <span class="e-pill ${o.estado}">${o.estado.replace('_',' ')}</span>
          </div>
          <div class="orden-card__srv">${esc(o.servicio)}</div>
          <div class="orden-card__cli">${o.cliente ? esc(o.cliente) : 'Sin cliente'}${o.placa ? ' • '+esc(o.placa) : ''}</div>
          <div class="pago-pill ${o.pagado ? 'pagado' : 'sin-pagar'}">${o.pagado ? '✓ Pagado' : '⏳ Sin pagar'}</div>
          ${o.extras_pendientes > 0 ? `<div class="extra-pill">⚡ ${o.extras_pendientes} extra${o.extras_pendientes>1?'s':''} en revisión</div>` : ''}
          ${o.estado === 'pendiente'  ? `<button class="orden-card__btn iniciar" data-accion="en_proceso" data-oid="${o.id}" type="button">▶ Iniciar</button>` : ''}
          ${o.estado === 'en_proceso' ? `<button class="orden-card__btn terminar" data-accion="terminado" data-oid="${o.id}" type="button">✓ Terminar</button>` : ''}
          ${o.estado === 'terminado'  ? `<div class="orden-card__esperando">Esperando entrega</div>` : ''}
          <button class="det-toggle" data-detoggle="${o.id}" type="button">${abierto ? '▲ Ocultar' : '▼ Pedir extra por dificultad'}</button>
          <div class="det-wrap ${abierto ? 'open' : ''}" id="det-${o.id}"></div>
        </div>`;
      }).join('');

      list.querySelectorAll('[data-accion]').forEach(btn => {
        btn.addEventListener('click', async () => {
          try {
            const r = await fetch(`${API}/autolavado/update_estado.php`, {
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
          if (state.detalleAbierto[oid]) cargarDetalleExtras(oid);
          else cargarActivos();
        });
      });

      // Si un panel de detalle quedó marcado como abierto (p.ej. el refresco
      // automático de 20s reconstruyó la lista), su contenido se recrea vacío.
      // Lo repoblamos para no dejar al usuario con un panel en blanco.
      Object.keys(state.detalleAbierto).forEach(oid => {
        if (state.detalleAbierto[oid] && activos.some(o => o.id === Number(oid))) {
          cargarDetalleExtras(Number(oid));
        }
      });
    } catch (e) {
      list.innerHTML = `<div class="empty">Error: ${esc(e.message)}</div>`;
    }
  }

  async function cargarDetalleExtras(ordenId) {
    const wrap = $('det-' + ordenId);
    if (!wrap) return;
    wrap.classList.add('open');
    wrap.innerHTML = '<div class="empty">Cargando…</div>';
    try {
      const r = await fetch(`${API}/autolavado/extras/listar.php?orden_id=${ordenId}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      const ESTADO_EXT = { pendiente: 'En revisión', aprobado: 'Aprobado', rechazado: 'Rechazado' };
      const extrasList = j.data || [];
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
        <div class="det-extras" style="border-top:none;padding-top:0;margin-top:6px">
          <div>${extrasHtml}</div>
          <div class="det-extra-form">
            <div class="field">
              <label>Monto sugerido</label>
              <input class="input" type="number" min="1" step="0.01" placeholder="Ej: 80" id="extMonto-${ordenId}">
            </div>
            <div class="field">
              <label>Razón (ej. tipo/tamaño de moto, muy sucia)</label>
              <textarea class="input" rows="2" placeholder="Ej: Italika AT110 con mucho lodo" id="extJust-${ordenId}"></textarea>
            </div>
            <button class="btn btn--ghost" style="width:100%" data-addext="${ordenId}" type="button">+ Enviar extra a revisión</button>
          </div>
        </div>`;

      wrap.querySelector(`[data-addext="${ordenId}"]`).addEventListener('click', async () => {
        const btnExt = wrap.querySelector(`[data-addext="${ordenId}"]`);
        const monto = Number($('extMonto-' + ordenId).value);
        const just = $('extJust-' + ordenId).value.trim();
        if (!monto || monto <= 0) { toast('Falta el monto', 'Escribe un monto sugerido mayor a 0.'); return; }
        if (!just) { toast('Falta la razón', 'Explica por qué se necesita el extra.'); return; }
        btnExt.disabled = true;
        try {
          const re = await fetch(`${API}/autolavado/extras/crear.php`, {
            method: 'POST', headers: {'Content-Type':'application/json'},
            body: JSON.stringify({ orden_id: ordenId, monto_sugerido: monto, justificacion: just }),
          });
          const je = await re.json();
          if (!je.ok) throw new Error(je.error);
          toast('Extra enviado', 'Caja lo revisará antes de cobrar.');
          cargarDetalleExtras(ordenId);
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
