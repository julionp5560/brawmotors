<?php
declare(strict_types=1);
require_once __DIR__ . '/../includes/guard_movil.php';
require_once __DIR__ . '/../includes/functions.php';

// Agenda compartida: a diferencia de taller.php/autolavado.php, aquí SÍ
// entran tanto Mecánico como Lavador (no se redirige por operario_modulo)
// porque las citas no son exclusivas de un área.
$nombre = htmlspecialchars((string)($_SESSION['user']['nombre'] ?? ''), ENT_QUOTES, 'UTF-8');
$tema = current_tema();
$volverA = operario_modulo() === 'taller' ? 'taller.php' : 'autolavado.php';
?>
<!doctype html>
<html lang="es" data-tema="<?= htmlspecialchars($tema, ENT_QUOTES, 'UTF-8') ?>">
<head>
<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1, viewport-fit=cover">
<title>Agenda · BRAW MOTORS</title>
<link rel="manifest" href="manifest.json">
<meta name="theme-color" content="#a52727">
<link rel="apple-touch-icon" href="icons/icon-192.png">
<meta name="mobile-web-app-capable" content="yes">
<meta name="apple-mobile-web-app-capable" content="yes">
<link rel="stylesheet" href="movil.css?v=5">
<style>
.ag-nav { display:flex; align-items:center; gap:8px; margin-bottom:10px; }
.ag-nav button { flex:0 0 auto; border:1px solid var(--border); background:rgba(255,255,255,.05); color:var(--text); border-radius:10px; padding:10px 12px; font-weight:800; }
.ag-nav .input { flex:1; text-align:center; }
.ag-fecha-label { font-weight:900; font-size:13px; text-align:center; margin-bottom:12px; text-transform:capitalize; color:var(--muted); }
.cita-card { border:1px solid var(--border); border-radius:14px; padding:12px; margin-bottom:10px; background:rgba(255,255,255,.04); }
.cita-card.completada { opacity:.6; }
.cita-card.cancelada { opacity:.5; text-decoration:line-through; }
.cita-card__top { display:flex; justify-content:space-between; align-items:center; margin-bottom:6px; }
.cita-card__hora { font-weight:950; font-size:16px; color:var(--primary2); }
.cita-area { font-size:10px; font-weight:900; text-transform:uppercase; border-radius:999px; padding:3px 9px; border:1px solid; }
.cita-area.taller { border-color:rgba(60,140,255,.4); background:rgba(60,140,255,.1); color:var(--info); }
.cita-area.autolavado { border-color:rgba(60,180,120,.4); background:rgba(60,180,120,.1); color:var(--ok); }
.cita-area.general { border-color:var(--border); background:rgba(255,255,255,.05); color:var(--muted); }
.cita-card__titulo { font-weight:800; font-size:14px; }
.cita-card__cliente { font-size:12px; color:var(--muted); margin-top:2px; }
.cita-card__notas { font-size:12px; color:var(--muted); margin-top:4px; font-style:italic; }
.cita-card__btns { display:flex; gap:6px; margin-top:10px; }
.cita-card__btns button { flex:1; border:1px solid var(--border); background:rgba(255,255,255,.05); color:var(--text); border-radius:10px; padding:8px; font-size:12px; font-weight:800; }
[data-tema="alto_contraste"] .cita-card { background:#ffffff; }
[data-tema="alto_contraste"] .ag-nav button { background:#ffffff; }
[data-tema="alto_contraste"] .cita-card__btns button { background:#ffffff; }
</style>
</head>
<body>
  <header class="topbar">
    <div class="topbar__brand">
      <a href="<?= htmlspecialchars($volverA, ENT_QUOTES, 'UTF-8') ?>" style="text-decoration:none;color:inherit">
        <b>← Agenda</b>
      </a>
      <span>Citas del negocio</span>
    </div>
    <div class="topbar__user">
      <button class="tema-switch" type="button" id="btnTemaSwitch" title="Cambiar a modo alto contraste (para exteriores)">
        <span id="temaSwitchIcon"><?= $tema === 'alto_contraste' ? '☀️' : '🌙' ?></span>
      </button>
      <span><?= $nombre ?></span>
      <a class="btn-logout" href="logout.php">Salir</a>
    </div>
  </header>

  <div class="wrap">

    <div class="card">
      <div class="ag-nav">
        <button type="button" id="btnAgAnt">←</button>
        <input class="input" type="date" id="agFecha">
        <button type="button" id="btnAgSig">→</button>
      </div>
      <button class="btn btn--ghost" id="btnAgHoy" type="button" style="margin-bottom:10px">Ir a hoy</button>

      <div id="agendaList">
        <div class="empty">Cargando…</div>
      </div>

      <button class="btn" id="btnToggleNuevaCita" type="button" style="margin-top:10px">+ Nueva cita</button>

      <div id="formNuevaCita" style="display:none;margin-top:12px">
        <input type="hidden" id="citaId" value="0">
        <div class="field" style="margin-bottom:10px">
          <label>Nombre del cliente</label>
          <input class="input" id="citaNombre" type="text" placeholder="Nombre Apellido">
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Teléfono</label>
          <input class="input" id="citaTel" type="tel" placeholder="3411234567">
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Área</label>
          <select class="input" id="citaArea">
            <option value="general">General</option>
            <option value="taller">Taller</option>
            <option value="autolavado">Autolavado</option>
          </select>
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Motivo de la cita</label>
          <input class="input" id="citaTitulo" type="text" placeholder="Ej: Cambio de aceite, Lavado completo…">
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Servicio a realizar (opcional)</label>
          <select class="input" id="citaServicio">
            <option value="">Sin servicio específico</option>
          </select>
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Fecha</label>
          <input class="input" id="citaFecha" type="date">
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Hora</label>
          <input class="input" id="citaHora" type="time">
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Duración estimada</label>
          <select class="input" id="citaDuracion">
            <option value="30">30 min</option>
            <option value="60" selected>1 hora</option>
            <option value="90">1 hora 30 min</option>
            <option value="120">2 horas</option>
            <option value="180">3 horas</option>
            <option value="240">4 horas</option>
            <option value="360">6 horas</option>
            <option value="480">8 horas</option>
            <option value="720">12 horas</option>
            <option value="1440">24 horas</option>
          </select>
        </div>
        <div class="field" style="margin-bottom:10px">
          <label>Notas</label>
          <textarea class="input" id="citaNotas" rows="2" placeholder="Observaciones…"></textarea>
        </div>
        <button class="btn" id="btnGuardarCita" type="button">Guardar cita</button>
      </div>
    </div>

  </div>

  <div class="toast" id="toast"><b id="toastTitle"></b><span id="toastMsg"></span></div>

<script>
(() => {
  const API = '../php/api';
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const AREA_LABEL = { taller: 'Taller', autolavado: 'Autolavado', general: 'General' };

  function hoyISO() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  }

  const state = { fecha: hoyISO(), citas: [], serviciosCache: null };

  // ── Selector de servicio (según el área elegida en el formulario) ──
  async function cargarServiciosCache() {
    if (state.serviciosCache) return state.serviciosCache;
    try {
      const r = await fetch(`${API}/servicios/list.php`);
      const j = await r.json();
      state.serviciosCache = j.ok ? (j.data || []) : [];
    } catch (e) { state.serviciosCache = []; }
    return state.serviciosCache;
  }

  async function poblarSelectServicio(areaFormulario) {
    const sel = $('citaServicio');
    const lista = await cargarServiciosCache();
    const filtrada = areaFormulario === 'general' ? lista : lista.filter(s => s.tipo === areaFormulario);
    sel.innerHTML = '<option value="">Sin servicio específico</option>' +
      filtrada.map(s => `<option value="${s.id}">${esc(s.nombre)}</option>`).join('');
  }

  $('citaArea').addEventListener('change', () => poblarSelectServicio($('citaArea').value));

  function toast(title, msg) {
    $('toastTitle').textContent = title;
    $('toastMsg').textContent = msg || '';
    $('toast').classList.add('show');
    clearTimeout(toast._t);
    toast._t = setTimeout(() => $('toast').classList.remove('show'), 3200);
  }

  // Toggle de modo alto contraste
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
          method: 'POST', headers: { 'Content-Type': 'application/json' },
          body: JSON.stringify({ tema: nuevo }),
        });
        const j = await res.json();
        if (!j.ok) throw new Error(j.error || 'No se pudo guardar');
      } catch (e) {
        toast('Aviso', 'El tema cambió, pero no se pudo guardar tu preferencia.');
      }
    });
  })();

  function fmtFechaLabel(iso) {
    const [y,m,d] = iso.split('-').map(Number);
    const dt = new Date(y, m-1, d);
    return dt.toLocaleDateString('es-MX', { weekday: 'long', day: 'numeric', month: 'long' });
  }

  function fmtHora(h) {
    const [hh, mm] = h.split(':').map(Number);
    const ampm = hh >= 12 ? 'PM' : 'AM';
    const h12 = hh % 12 === 0 ? 12 : hh % 12;
    return `${h12}:${String(mm).padStart(2,'0')} ${ampm}`;
  }

  async function cargar() {
    $('agFecha').value = state.fecha;
    $('agendaList').innerHTML = '<div class="empty">Cargando…</div>';
    try {
      const r = await fetch(`${API}/citas/list.php?fecha=${state.fecha}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      state.citas = j.data || [];
      render();
    } catch (e) {
      $('agendaList').innerHTML = `<div class="empty">Error: ${esc(e.message)}</div>`;
    }
  }

  function render() {
    if (!state.citas.length) {
      $('agendaList').innerHTML = `<div class="empty">Sin citas para ${esc(fmtFechaLabel(state.fecha))}.</div>`;
      return;
    }
    $('agendaList').innerHTML = state.citas.map(c => `
      <div class="cita-card ${c.estado}">
        <div class="cita-card__top">
          <span class="cita-card__hora">${fmtHora(c.hora)}</span>
          <span class="cita-area ${c.area}">${AREA_LABEL[c.area]||c.area}</span>
        </div>
        <div class="cita-card__titulo">${esc(c.titulo)}</div>
        <div class="cita-card__cliente">${esc(c.nombre_cliente)}${c.telefono ? ' • '+esc(c.telefono) : ''}</div>
        ${c.servicio_nombre ? `<div class="cita-card__notas">🔧 ${esc(c.servicio_nombre)}</div>` : ''}
        ${c.notas ? `<div class="cita-card__notas">${esc(c.notas)}</div>` : ''}
        ${!['completada','cancelada'].includes(c.estado) ? `
          <div class="cita-card__btns">
            <button data-completar="${c.id}" type="button">✓ Completar</button>
            <button data-cancelar="${c.id}" type="button">✕ Cancelar</button>
          </div>` : ''}
      </div>`).join('');

    $('agendaList').querySelectorAll('[data-completar]').forEach(btn => {
      btn.addEventListener('click', () => cambiarEstado(Number(btn.dataset.completar), 'completada'));
    });
    $('agendaList').querySelectorAll('[data-cancelar]').forEach(btn => {
      btn.addEventListener('click', () => {
        if (confirm('¿Cancelar esta cita?')) cambiarEstado(Number(btn.dataset.cancelar), 'cancelada');
      });
    });
  }

  async function cambiarEstado(id, estado) {
    try {
      const r = await fetch(`${API}/citas/estado.php`, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id, estado }),
      });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      toast('Listo', estado === 'completada' ? 'Cita completada.' : 'Cita cancelada.');
      cargar();
    } catch (e) { toast('Error', e.message); }
  }

  function sumarDias(iso, n) {
    const [y,m,d] = iso.split('-').map(Number);
    const dt = new Date(y, m-1, d);
    dt.setDate(dt.getDate() + n);
    return dt.getFullYear() + '-' + String(dt.getMonth()+1).padStart(2,'0') + '-' + String(dt.getDate()).padStart(2,'0');
  }

  $('btnAgAnt').addEventListener('click', () => { state.fecha = sumarDias(state.fecha, -1); cargar(); });
  $('btnAgSig').addEventListener('click', () => { state.fecha = sumarDias(state.fecha, 1); cargar(); });
  $('btnAgHoy').addEventListener('click', () => { state.fecha = hoyISO(); cargar(); });
  $('agFecha').addEventListener('change', () => { state.fecha = $('agFecha').value || hoyISO(); cargar(); });

  $('btnToggleNuevaCita').addEventListener('click', () => {
    const f = $('formNuevaCita');
    const abrir = f.style.display === 'none';
    f.style.display = abrir ? 'block' : 'none';
    if (abrir) {
      $('citaId').value = '0';
      $('citaNombre').value = '';
      $('citaTel').value = '';
      $('citaArea').value = 'general';
      $('citaTitulo').value = '';
      $('citaFecha').value = state.fecha;
      $('citaHora').value = '10:00';
      $('citaDuracion').value = '60';
      $('citaNotas').value = '';
      poblarSelectServicio('general');
    }
  });

  $('btnGuardarCita').addEventListener('click', async () => {
    const nombre = $('citaNombre').value.trim();
    const titulo = $('citaTitulo').value.trim();
    const fecha = $('citaFecha').value;
    const hora = $('citaHora').value;
    if (!nombre) { toast('Falta el nombre', 'Escribe el nombre del cliente.'); return; }
    if (!titulo) { toast('Falta el motivo', 'Describe brevemente la cita.'); return; }
    if (!fecha || !hora) { toast('Falta fecha u hora', ''); return; }

    const body = {
      id: Number($('citaId').value || 0),
      nombre_cliente: nombre, telefono: $('citaTel').value.trim(),
      area: $('citaArea').value, titulo,
      servicio_id: Number($('citaServicio').value || 0) || null,
      fecha, hora, duracion_min: Number($('citaDuracion').value),
      notas: $('citaNotas').value.trim(),
    };
    try {
      const r = await fetch(`${API}/citas/create.php`, {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify(body),
      });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      toast('Cita guardada', nombre);
      $('formNuevaCita').style.display = 'none';
      state.fecha = fecha;
      cargar();
    } catch (e) { toast('Error', e.message); }
  });

  cargar();
})();

if ('serviceWorker' in navigator) {
  navigator.serviceWorker.register('sw.js').catch(()=>{});
}
</script>
</body>
</html>
