<?php declare(strict_types=1); ?>

<style>
.agenda-toolbar {
  display: flex; align-items: center; gap: 10px; flex-wrap: wrap; margin-bottom: 14px;
}
.agenda-nav { display: flex; align-items: center; gap: 6px; }
.agenda-nav button {
  border: 1px solid var(--border); background: rgba(255,255,255,.04); color: var(--text);
  border-radius: 10px; padding: 8px 12px; cursor: pointer; font-weight: 800;
}
.agenda-nav button:hover { border-color: var(--primary2); }
.agenda-fecha-label { font-weight: 900; font-size: 14px; min-width: 220px; text-align: center; text-transform: capitalize; }
.agenda-list { display: flex; flex-direction: column; gap: 10px; }
.cita-card {
  display: flex; align-items: center; gap: 14px;
  background: linear-gradient(180deg, rgba(255,255,255,.06), rgba(255,255,255,.03));
  border: 1px solid var(--border); border-radius: 16px; padding: 14px 16px;
}
.cita-card.completada { opacity: .6; }
.cita-card.cancelada { opacity: .5; text-decoration: line-through; }
.cita-card__hora { font-size: 18px; font-weight: 950; color: var(--primary2); min-width: 68px; }
.cita-card__dur { font-size: 11px; color: var(--muted); }
.cita-card__body { flex: 1; min-width: 0; }
.cita-card__titulo { font-weight: 900; font-size: 14px; }
.cita-card__cliente { font-size: 12px; color: var(--muted); margin-top: 2px; }
.cita-card__notas { font-size: 12px; color: var(--muted); margin-top: 4px; font-style: italic; }
.cita-area {
  font-size: 10px; font-weight: 900; letter-spacing: .5px; text-transform: uppercase;
  border-radius: 999px; padding: 3px 10px; border: 1px solid;
}
.cita-area.taller      { border-color: rgba(60,140,255,.4); background: rgba(60,140,255,.1); color: #3c8cff; }
.cita-area.autolavado  { border-color: rgba(60,180,120,.4); background: rgba(60,180,120,.1); color: #3cb478; }
.cita-area.general     { border-color: rgba(255,255,255,.2); background: rgba(255,255,255,.06); color: var(--muted); }
.cita-card__acciones { display: flex; gap: 6px; flex-shrink: 0; }
.agenda-empty { text-align: center; color: var(--muted); padding: 40px 0; }
</style>

<div class="agenda-toolbar">
  <div class="agenda-nav">
    <button type="button" id="btnDiaAnt">←</button>
    <input class="input" type="date" id="agendaFecha" style="max-width:170px">
    <button type="button" id="btnDiaSig">→</button>
    <button type="button" id="btnHoy">Hoy</button>
  </div>
  <select class="select" id="agendaArea" style="max-width:180px;padding:10px 12px;border-radius:14px">
    <option value="">Todas las áreas</option>
    <option value="taller">Taller</option>
    <option value="autolavado">Autolavado</option>
    <option value="general">General</option>
  </select>
  <span class="pill" id="agendaCount">—</span>
  <div style="flex:1"></div>
  <button class="btn" id="btnNuevaCita" type="button">+ Nueva cita</button>
</div>

<div class="agenda-list" id="agendaList">
  <div class="muted" style="padding:20px 0">Cargando…</div>
</div>

<!-- MODAL: nueva/editar cita -->
<div class="modal" id="modalCita" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close="modalCita"></div>
  <div class="modal__dialog" style="max-width:480px" role="dialog">
    <div class="modal__header">
      <div><h3 class="modal__title" id="citaModalTitle">Nueva cita</h3></div>
      <button class="modal__close" type="button" data-modal-close="modalCita">✕</button>
    </div>
    <div class="modal__body">
      <input type="hidden" id="citaId" value="0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field" style="grid-column:1/-1">
          <label>Nombre del cliente *</label>
          <input class="select" id="citaNombre" type="text" placeholder="Nombre Apellido" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Teléfono</label>
          <input class="select" id="citaTel" type="tel" placeholder="3411234567" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Área *</label>
          <select class="select" id="citaArea" style="width:100%;padding:10px 12px;border-radius:14px">
            <option value="general">General</option>
            <option value="taller">Taller</option>
            <option value="autolavado">Autolavado</option>
          </select>
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Motivo de la cita *</label>
          <input class="select" id="citaTitulo" type="text" placeholder="Ej: Cambio de aceite, Lavado completo…" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Servicio a realizar <span style="color:var(--muted);font-weight:400">(opcional)</span></label>
          <select class="select" id="citaServicio" style="width:100%;padding:10px 12px;border-radius:14px">
            <option value="">Sin servicio específico</option>
          </select>
        </div>
        <div class="field">
          <label>Fecha *</label>
          <input class="select" id="citaFecha" type="date" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Hora *</label>
          <input class="select" id="citaHora" type="time" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Duración estimada</label>
          <select class="select" id="citaDuracion" style="width:100%;padding:10px 12px;border-radius:14px">
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
        <div class="field" style="grid-column:1/-1">
          <label>Notas</label>
          <textarea class="select" id="citaNotas" rows="2" placeholder="Observaciones…" style="width:100%;padding:10px 12px;border-radius:14px;resize:none"></textarea>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalCita">Cancelar</button>
      <button class="btn" id="btnGuardarCita" type="button">Guardar cita</button>
    </div>
  </div>
</div>

<script>
(() => {
  const $ = id => document.getElementById(id);
  const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');

  const AREA_LABEL = { taller: 'Taller', autolavado: 'Autolavado', general: 'General' };

  function hoyISO() {
    const d = new Date();
    return d.getFullYear() + '-' + String(d.getMonth()+1).padStart(2,'0') + '-' + String(d.getDate()).padStart(2,'0');
  }

  const state = { fecha: hoyISO(), area: '', citas: [], serviciosCache: null };

  // ── Selector de servicio (según el área elegida en el formulario) ──
  async function cargarServiciosCache() {
    if (state.serviciosCache) return state.serviciosCache;
    try {
      const r = await fetch('php/api/servicios/list.php');
      const j = await r.json();
      state.serviciosCache = j.ok ? (j.data || []) : [];
    } catch (e) { state.serviciosCache = []; }
    return state.serviciosCache;
  }

  async function poblarSelectServicio(areaFormulario, servicioSeleccionado) {
    const sel = $('citaServicio');
    const lista = await cargarServiciosCache();
    const filtrada = areaFormulario === 'general' ? lista : lista.filter(s => s.tipo === areaFormulario);
    sel.innerHTML = '<option value="">Sin servicio específico</option>' +
      filtrada.map(s => `<option value="${s.id}">${esc(s.nombre)}</option>`).join('');
    sel.value = servicioSeleccionado ? String(servicioSeleccionado) : '';
  }

  $('citaArea').addEventListener('change', () => poblarSelectServicio($('citaArea').value, null));

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

  function fmtDuracion(min) {
    min = Number(min);
    if (min < 60) return `${min} min`;
    const h = Math.floor(min/60), m = min % 60;
    return m ? `${h}h ${m}min` : `${h}h`;
  }

  async function cargar() {
    $('agendaList').innerHTML = '<div class="muted" style="padding:20px 0">Cargando…</div>';
    try {
      const params = new URLSearchParams({ fecha: state.fecha });
      if (state.area) params.set('area', state.area);
      const r = await fetch(`php/api/citas/list.php?${params.toString()}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      state.citas = j.data || [];
      render();
    } catch (e) {
      $('agendaList').innerHTML = `<div class="muted" style="padding:20px 0">Error: ${esc(e.message)}</div>`;
    }
  }

  function render() {
    $('agendaFecha').value = state.fecha;
    $('agendaCount').textContent = `${state.citas.length} cita${state.citas.length===1?'':'s'}`;

    if (!state.citas.length) {
      $('agendaList').innerHTML = `<div class="agenda-empty">Sin citas para ${esc(fmtFechaLabel(state.fecha))}.</div>`;
      return;
    }

    $('agendaList').innerHTML = state.citas.map(c => `
      <div class="cita-card ${c.estado}">
        <div>
          <div class="cita-card__hora">${fmtHora(c.hora)}</div>
          <div class="cita-card__dur">${fmtDuracion(c.duracion_min)}</div>
        </div>
        <div class="cita-card__body">
          <span class="cita-area ${c.area}">${AREA_LABEL[c.area]||c.area}</span>
          <div class="cita-card__titulo">${esc(c.titulo)}</div>
          <div class="cita-card__cliente">${esc(c.nombre_cliente)}${c.telefono ? ' • '+esc(c.telefono) : ''}</div>
          ${c.servicio_nombre ? `<div class="cita-card__notas">🔧 ${esc(c.servicio_nombre)}</div>` : ''}
          ${c.notas ? `<div class="cita-card__notas">${esc(c.notas)}</div>` : ''}
        </div>
        <div class="cita-card__acciones">
          ${!['completada','cancelada'].includes(c.estado) ? `
            <button class="btn-sm" data-editar="${c.id}" type="button">Editar</button>
            <button class="btn-sm" data-completar="${c.id}" type="button">✓ Completar</button>
            <button class="btn-sm" data-cancelar="${c.id}" type="button" style="color:#ff5a5a">✕ Cancelar</button>
          ` : `<span class="muted" style="font-size:12px;text-transform:capitalize">${c.estado}</span>`}
        </div>
      </div>`).join('');

    $('agendaList').querySelectorAll('[data-editar]').forEach(btn => {
      btn.addEventListener('click', () => abrirEdicion(Number(btn.dataset.editar)));
    });
    $('agendaList').querySelectorAll('[data-completar]').forEach(btn => {
      btn.addEventListener('click', () => cambiarEstado(Number(btn.dataset.completar), 'completada'));
    });
    $('agendaList').querySelectorAll('[data-cancelar]').forEach(btn => {
      btn.addEventListener('click', () => {
        if (window.confirm('¿Cancelar esta cita?')) cambiarEstado(Number(btn.dataset.cancelar), 'cancelada');
      });
    });
  }

  async function cambiarEstado(id, estado) {
    try {
      const r = await fetch('php/api/citas/estado.php', {
        method: 'POST', headers: {'Content-Type':'application/json'},
        body: JSON.stringify({ id, estado }),
      });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      toast('Listo', estado === 'completada' ? 'Cita marcada como completada.' : 'Cita cancelada.', {icon:'✓'});
      cargar();
    } catch (e) { toast('Error', e.message, {icon:'⚠️'}); }
  }

  function limpiarForm() {
    $('citaId').value = '0';
    $('citaModalTitle').textContent = 'Nueva cita';
    $('citaNombre').value = '';
    $('citaTel').value = '';
    $('citaArea').value = 'general';
    $('citaTitulo').value = '';
    $('citaFecha').value = state.fecha;
    $('citaHora').value = '10:00';
    $('citaDuracion').value = '60';
    $('citaNotas').value = '';
    poblarSelectServicio('general', null);
  }

  $('btnNuevaCita').addEventListener('click', () => {
    limpiarForm();
    modalOpen('modalCita');
    setTimeout(() => $('citaNombre')?.focus(), 60);
  });

  function abrirEdicion(id) {
    const c = state.citas.find(x => x.id === id);
    if (!c) return;
    $('citaId').value = c.id;
    $('citaModalTitle').textContent = 'Editar cita';
    $('citaNombre').value = c.nombre_cliente || '';
    $('citaTel').value = c.telefono || '';
    $('citaArea').value = c.area || 'general';
    $('citaTitulo').value = c.titulo || '';
    $('citaFecha').value = c.fecha;
    $('citaHora').value = c.hora.slice(0,5);
    $('citaDuracion').value = String(c.duracion_min);
    $('citaNotas').value = c.notas || '';
    poblarSelectServicio(c.area || 'general', c.servicio_id);
    modalOpen('modalCita');
  }

  $('btnGuardarCita').addEventListener('click', async () => {
    const nombre = $('citaNombre').value.trim();
    const titulo = $('citaTitulo').value.trim();
    const fecha = $('citaFecha').value;
    const hora = $('citaHora').value;
    if (!nombre) { toast('Falta el nombre', 'Escribe el nombre del cliente.', {icon:'⚠️'}); return; }
    if (!titulo) { toast('Falta el motivo', 'Describe brevemente la cita.', {icon:'⚠️'}); return; }
    if (!fecha || !hora) { toast('Falta fecha u hora', '', {icon:'⚠️'}); return; }

    const id = Number($('citaId').value || 0);
    const body = {
      id, nombre_cliente: nombre, telefono: $('citaTel').value.trim(),
      area: $('citaArea').value, titulo,
      servicio_id: Number($('citaServicio').value || 0) || null,
      fecha, hora, duracion_min: Number($('citaDuracion').value),
      notas: $('citaNotas').value.trim(),
    };
    const url = id > 0 ? 'php/api/citas/update.php' : 'php/api/citas/create.php';
    try {
      const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      modalClose('modalCita');
      toast('Guardado', `Cita para ${nombre}`, {icon:'📅'});
      state.fecha = fecha;
      cargar();
    } catch (e) { toast('Error', e.message, {icon:'⚠️'}); }
  });

  // ── Navegación de fecha ──
  function sumarDias(iso, n) {
    const [y,m,d] = iso.split('-').map(Number);
    const dt = new Date(y, m-1, d);
    dt.setDate(dt.getDate() + n);
    return dt.getFullYear() + '-' + String(dt.getMonth()+1).padStart(2,'0') + '-' + String(dt.getDate()).padStart(2,'0');
  }

  $('btnDiaAnt').addEventListener('click', () => { state.fecha = sumarDias(state.fecha, -1); cargar(); });
  $('btnDiaSig').addEventListener('click', () => { state.fecha = sumarDias(state.fecha, 1); cargar(); });
  $('btnHoy').addEventListener('click', () => { state.fecha = hoyISO(); cargar(); });
  $('agendaFecha').addEventListener('change', () => { state.fecha = $('agendaFecha').value || hoyISO(); cargar(); });
  $('agendaArea').addEventListener('change', () => { state.area = $('agendaArea').value; cargar(); });

  cargar();
})();
</script>
