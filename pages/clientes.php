<?php declare(strict_types=1); ?>

<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Clientes</div>
      <span class="pill" id="cliCount">—</span>
    </div>
    <div class="table-toolbar__right">
      <input class="input" id="cliSearch" type="search" placeholder="Buscar nombre o teléfono…" />
      <button class="btn" id="btnNuevoCli" type="button">+ Nuevo cliente</button>
    </div>
  </div>

  <table class="table" id="cliTable">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Teléfono</th>
        <th>Email</th>
        <th>RFC</th>
        <th>Registro</th>
        <th class="num">Estado</th>
      </tr>
    </thead>
    <tbody id="cliTbody">
      <tr><td colspan="6" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</div>

<!-- MODAL: nuevo/editar cliente -->
<div class="modal" id="modalCliente" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close="modalCliente"></div>
  <div class="modal__dialog" style="max-width:520px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title" id="cliModalTitle">Nuevo cliente</h3>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalCliente">✕</button>
    </div>
    <div class="modal__body">
      <input type="hidden" id="cliId" value="0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field" style="grid-column:1/-1">
          <label>Nombre completo *</label>
          <input class="select" id="cliNombre" type="text" placeholder="Nombre Apellido" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Teléfono *</label>
          <input class="select" id="cliTel" type="tel" placeholder="3411234567" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Email</label>
          <input class="select" id="cliEmail" type="email" placeholder="correo@ejemplo.com" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>RFC</label>
          <input class="select" id="cliRfc" type="text" placeholder="XAXX010101000" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Fecha nacimiento</label>
          <input class="select" id="cliFechaNac" type="date" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Notas</label>
          <textarea class="select" id="cliNotas" rows="2" placeholder="Observaciones…" style="width:100%;padding:10px 12px;border-radius:14px;resize:none"></textarea>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalCliente">Cancelar</button>
      <button class="btn" id="btnGuardarCli" type="button">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL: historial de cliente -->
<div class="modal" id="modalHistorialCli" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close="modalHistorialCli"></div>
  <div class="modal__dialog" style="max-width:680px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title" id="histClienteNombre">Historial</h3>
        <p class="modal__subtitle" id="histClienteMeta"></p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalHistorialCli">✕</button>
    </div>
    <div class="modal__body" id="histBody" style="max-height:60vh;overflow:auto">
      <div class="muted" style="padding:16px 0">Cargando…</div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalHistorialCli">Cerrar</button>
    </div>
  </div>
</div>

<!-- MODAL: vehículos del cliente -->
<div class="modal" id="modalVehiculosCli" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close="modalVehiculosCli"></div>
  <div class="modal__dialog" style="max-width:680px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Vehículos</h3>
        <p class="modal__subtitle" id="vehClienteNombre"></p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalVehiculosCli">✕</button>
    </div>
    <div class="modal__body" style="max-height:64vh;overflow:auto">
      <input type="hidden" id="vehClienteId" value="0">
      <input type="hidden" id="vehId" value="0">

      <table class="table" id="vehTable">
        <thead>
          <tr>
            <th>Vehículo</th>
            <th>Placas</th>
            <th>Estado</th>
            <th class="num">Acciones</th>
          </tr>
        </thead>
        <tbody id="vehTbody">
          <tr><td colspan="4" class="muted">Cargando…</td></tr>
        </tbody>
      </table>

      <div class="hr"></div>

      <div style="display:flex;align-items:center;justify-content:space-between;margin-bottom:8px">
        <div style="font-weight:800" id="vehFormTitle">Nuevo vehículo</div>
        <button class="btn-sm" type="button" id="btnVehLimpiar">Limpiar</button>
      </div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field" style="grid-column:1/-1">
          <label>Marca *</label>
          <input class="select" id="vehMarca" type="text" placeholder="Italika, Honda…" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Modelo</label>
          <input class="select" id="vehModelo" type="text" placeholder="FT150" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Año</label>
          <input class="select" id="vehAnio" type="number" placeholder="2023" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Color</label>
          <input class="select" id="vehColor" type="text" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Placas</label>
          <input class="select" id="vehPlacas" type="text" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Número de serie</label>
          <input class="select" id="vehSerie" type="text" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Kilometraje</label>
          <input class="select" id="vehKm" type="number" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Notas</label>
          <textarea class="select" id="vehNotas" rows="2" style="width:100%;padding:10px 12px;border-radius:14px;resize:none"></textarea>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalVehiculosCli">Cerrar</button>
      <button class="btn" id="btnGuardarVeh" type="button">Guardar vehículo</button>
    </div>
  </div>
</div>

<script>
(() => {
  const esc = s => String(s??'').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  let CLIENTES = [], q = '';

  const $ = id => document.getElementById(id);

  async function load() {
    $('cliTbody').innerHTML = '<tr><td colspan="6" class="muted">Cargando…</td></tr>';
    const r = await fetch(`php/api/clientes/list.php?q=${encodeURIComponent(q)}`);
    const j = await r.json();
    CLIENTES = j.data || [];
    render();
  }

  function render() {
    $('cliCount').textContent = `${CLIENTES.length} clientes`;
    if (!CLIENTES.length) {
      $('cliTbody').innerHTML = '<tr><td colspan="6" class="muted">Sin resultados.</td></tr>';
      return;
    }
    $('cliTbody').innerHTML = CLIENTES.map(c => `
      <tr>
        <td><b>${esc(c.nombre_completo)}</b></td>
        <td>${esc(c.telefono||'—')}</td>
        <td>${esc(c.email||'—')}</td>
        <td>${esc(c.rfc||'—')}</td>
        <td style="font-size:12px;color:var(--muted)">${esc((c.fecha_registro||'').slice(0,10))}</td>
        <td class="num">
          <div class="row-actions">
            <button class="btn-sm" onclick="editarCli(${c.id})">Editar</button>
            <button class="btn-sm" onclick="verHistorialCli(${c.id})">Historial</button>
            <button class="btn-sm" onclick="abrirVehiculosCli(${c.id}, '${esc(c.nombre_completo).replace(/'/g, "\\'")}')">Vehículos</button>
          </div>
        </td>
      </tr>`).join('');
  }

  $('cliSearch').addEventListener('input', () => { q = $('cliSearch').value; load(); });

  $('btnNuevoCli').addEventListener('click', () => {
    $('cliModalTitle').textContent = 'Nuevo cliente';
    ['cliId','cliNombre','cliTel','cliEmail','cliRfc','cliFechaNac','cliNotas'].forEach(id => {
      const el=$(id); if(el) el.value = id==='cliId'?'0':'';
    });
    modalOpen('modalCliente');
    setTimeout(() => $('cliNombre')?.focus(), 60);
  });

  window.editarCli = (id) => {
    const c = CLIENTES.find(x => x.id == id);
    if (!c) return;
    $('cliModalTitle').textContent = 'Editar cliente';
    $('cliId').value        = c.id;
    $('cliNombre').value    = c.nombre_completo || '';
    $('cliTel').value       = c.telefono  || '';
    $('cliEmail').value     = c.email     || '';
    $('cliRfc').value       = c.rfc       || '';
    $('cliFechaNac').value  = (c.fecha_nacimiento||'').slice(0,10);
    $('cliNotas').value     = c.notas     || '';
    modalOpen('modalCliente');
  };

  $('btnGuardarCli').addEventListener('click', async () => {
    const nombre = $('cliNombre').value.trim();
    const tel    = $('cliTel').value.trim();
    const id     = Number($('cliId').value);
    if (!nombre || !tel) { toast('Faltan datos', 'Nombre y teléfono son requeridos.', {icon:'⚠️'}); return; }

    const url = id ? 'php/api/clientes/update.php' : 'php/api/clientes/create.php';
    const body = {
      id, nombre_completo: nombre, telefono: tel,
      email: $('cliEmail').value.trim(),
      rfc:   $('cliRfc').value.trim(),
      fecha_nacimiento: $('cliFechaNac').value || null,
      notas: $('cliNotas').value.trim(),
    };
    try {
      const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error);
      modalClose('modalCliente');
      toast('Guardado', nombre, {icon:'👤'});
      load();
    } catch(e) { toast('Error', e.message, {icon:'⚠️'}); }
  });

  // ── Historial de cliente ───────────────────────────
  const fmtMoney = n => `$${Number(n||0).toFixed(2)}`;
  const fmtFecha = s => (s||'').slice(0,10);

  window.verHistorialCli = async (id) => {
    $('histClienteNombre').textContent = 'Historial';
    $('histClienteMeta').textContent = '';
    $('histBody').innerHTML = '<div class="muted" style="padding:16px 0">Cargando…</div>';
    modalOpen('modalHistorialCli');

    try {
      const r = await fetch(`php/api/clientes/historial.php?cliente_id=${id}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo cargar el historial');

      const { cliente, vehiculos, ordenes, ventas, resumen } = j.data;

      $('histClienteNombre').textContent = cliente.nombre_completo;
      $('histClienteMeta').textContent = [cliente.telefono, cliente.email].filter(Boolean).join(' • ') || 'Sin contacto registrado';

      const vehHtml = vehiculos.length
        ? vehiculos.map(v => `<span class="tag" style="margin:2px">${esc(v.marca)} ${esc(v.modelo||'')} ${v.año||''}${v.placas ? ' · '+esc(v.placas) : ''}</span>`).join('')
        : '<span class="muted">Sin vehículos registrados.</span>';

      const ordenesHtml = ordenes.length
        ? `<table class="table" style="margin-top:6px">
            <thead><tr><th>Folio</th><th>Vehículo</th><th>Estado</th><th class="num">Total</th><th>Fecha</th></tr></thead>
            <tbody>${ordenes.map(o => `
              <tr>
                <td><b>${esc(o.folio)}</b></td>
                <td>${esc([o.vehiculo_marca,o.vehiculo_modelo].filter(Boolean).join(' ')) || '—'}</td>
                <td>${esc(o.estado||'—')}</td>
                <td class="num">${fmtMoney(o.total)}</td>
                <td style="font-size:12px;color:var(--muted)">${fmtFecha(o.fecha_entrada)}</td>
              </tr>`).join('')}</tbody>
          </table>`
        : '<div class="muted" style="padding:8px 0">Sin órdenes de taller.</div>';

      const ventasHtml = ventas.length
        ? `<table class="table" style="margin-top:6px">
            <thead><tr><th>Folio</th><th>Tipo</th><th>Estado</th><th class="num">Total</th><th>Fecha</th></tr></thead>
            <tbody>${ventas.map(v => `
              <tr>
                <td><b>${esc(v.folio)}</b></td>
                <td>${esc(v.tipo||'—')}</td>
                <td>${esc(v.estado||'—')}</td>
                <td class="num">${fmtMoney(v.total)}</td>
                <td style="font-size:12px;color:var(--muted)">${fmtFecha(v.fecha)}</td>
              </tr>`).join('')}</tbody>
          </table>`
        : '<div class="muted" style="padding:8px 0">Sin ventas registradas.</div>';

      $('histBody').innerHTML = `
        <div style="display:flex;gap:16px;flex-wrap:wrap;margin-bottom:14px">
          <div class="kpi"><div class="kpi__label">Total gastado</div><div class="kpi__value">${fmtMoney(resumen.total_gastado)}</div></div>
          <div class="kpi"><div class="kpi__label">Visitas</div><div class="kpi__value">${resumen.visitas}</div></div>
          <div class="kpi"><div class="kpi__label">Última visita</div><div class="kpi__value" style="font-size:16px">${fmtFecha(resumen.ultima_visita) || '—'}</div></div>
        </div>
        <div style="font-weight:800;margin-bottom:6px;">Vehículos</div>
        <div>${vehHtml}</div>
        <div style="font-weight:800;margin:14px 0 0;">Órdenes de taller</div>
        ${ordenesHtml}
        <div style="font-weight:800;margin:14px 0 0;">Ventas</div>
        ${ventasHtml}
      `;
    } catch (e) {
      $('histBody').innerHTML = `<div class="muted" style="padding:16px 0">Error: ${esc(e.message||e)}</div>`;
    }
  };

  // ── Vehículos del cliente ───────────────────────────
  let VEHICULOS = [];

  function limpiarFormVeh() {
    $('vehId').value = '0';
    $('vehFormTitle').textContent = 'Nuevo vehículo';
    ['vehMarca','vehModelo','vehAnio','vehColor','vehPlacas','vehSerie','vehKm','vehNotas'].forEach(id => {
      const el = $(id); if (el) el.value = '';
    });
  }

  async function cargarVehiculosCli() {
    const cid = $('vehClienteId').value;
    $('vehTbody').innerHTML = '<tr><td colspan="4" class="muted">Cargando…</td></tr>';
    try {
      const r = await fetch(`php/api/vehiculos/list.php?cliente_id=${cid}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudieron cargar vehículos');
      VEHICULOS = j.data || [];
      renderVehiculosCli();
    } catch (e) {
      $('vehTbody').innerHTML = `<tr><td colspan="4" class="muted">Error: ${esc(e.message||e)}</td></tr>`;
    }
  }

  function renderVehiculosCli() {
    if (!VEHICULOS.length) {
      $('vehTbody').innerHTML = '<tr><td colspan="4" class="muted">Sin vehículos registrados.</td></tr>';
      return;
    }
    $('vehTbody').innerHTML = VEHICULOS.map(v => {
      const activo = (v.activo == 1 || v.activo === '1');
      return `
      <tr>
        <td><b>${esc(v.marca)} ${esc(v.modelo||'')}</b> ${v.año||''}</td>
        <td>${esc(v.placas||'—')}</td>
        <td><span class="tag ${activo?'tag--ok':'tag--bad'}">${activo?'Activo':'Inactivo'}</span></td>
        <td class="num">
          <div class="row-actions">
            <button class="btn-sm" data-veh-edit="${v.id}">Editar</button>
            <button class="btn-sm" data-veh-del="${v.id}">Eliminar</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  window.abrirVehiculosCli = async (clienteId, clienteNombre) => {
    $('vehClienteId').value = clienteId;
    $('vehClienteNombre').textContent = clienteNombre || '';
    limpiarFormVeh();
    modalOpen('modalVehiculosCli');
    await cargarVehiculosCli();
  };

  $('vehTbody').addEventListener('click', (e) => {
    const btnEdit = e.target.closest('[data-veh-edit]');
    if (btnEdit) {
      const id = Number(btnEdit.getAttribute('data-veh-edit'));
      const v = VEHICULOS.find(x => Number(x.id) === id);
      if (!v) return;
      $('vehId').value = v.id;
      $('vehFormTitle').textContent = 'Editar vehículo';
      $('vehMarca').value = v.marca || '';
      $('vehModelo').value = v.modelo || '';
      $('vehAnio').value = v.año || '';
      $('vehColor').value = v.color || '';
      $('vehPlacas').value = v.placas || '';
      $('vehSerie').value = v.numero_serie || '';
      $('vehKm').value = v.kilometraje || '';
      $('vehNotas').value = v.notas || '';
      return;
    }
    const btnDel = e.target.closest('[data-veh-del]');
    if (btnDel) {
      const id = Number(btnDel.getAttribute('data-veh-del'));
      eliminarVehiculo(id);
    }
  });

  $('btnVehLimpiar').addEventListener('click', limpiarFormVeh);

  $('btnGuardarVeh').addEventListener('click', async () => {
    const id = Number($('vehId').value || 0);
    const marca = $('vehMarca').value.trim();
    if (!marca) { toast('Falta marca', 'La marca es requerida.', {icon:'⚠️'}); return; }

    const body = {
      id,
      cliente_id: Number($('vehClienteId').value),
      marca,
      modelo: $('vehModelo').value.trim(),
      año: $('vehAnio').value || null,
      color: $('vehColor').value.trim(),
      placas: $('vehPlacas').value.trim(),
      numero_serie: $('vehSerie').value.trim(),
      kilometraje: $('vehKm').value || null,
      notas: $('vehNotas').value.trim(),
      activo: 1,
    };

    const url = id > 0 ? 'php/api/vehiculos/update.php' : 'php/api/vehiculos/create.php';
    try {
      const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo guardar');
      toast('Guardado', marca, {icon:'🏍️'});
      limpiarFormVeh();
      await cargarVehiculosCli();
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  });

  async function eliminarVehiculo(id) {
    const ok = window.confirm('¿Eliminar este vehículo? Si ya tiene órdenes de taller registradas, se marcará como inactivo en vez de borrarse.');
    if (!ok) return;
    try {
      const r = await fetch('php/api/vehiculos/delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo eliminar');
      toast('Listo', j.mode === 'soft' ? 'Vehículo marcado como inactivo.' : 'Vehículo eliminado.', {icon:'✓'});
      await cargarVehiculosCli();
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  }

  load();
})();
</script>
