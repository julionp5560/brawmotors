<?php
declare(strict_types=1);
require_once __DIR__ . "/../config/db.php";
$dbInfo = "{$DB_NAME} @ {$DB_HOST}";
?>
<div style="max-width:900px">
  <div class="panel">
    <div class="panel__head">
      <h3>Ajustes del sistema</h3>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label style="color:var(--muted);font-size:12px;font-weight:900">Nombre del negocio</label>
      <input class="select" type="text" value="<?= e($NEGOCIO_NOMBRE ?? 'BRAW MOTORS') ?>" disabled style="width:100%;padding:10px 12px;border-radius:14px;margin-top:6px;opacity:.6">
      <div class="xs muted" style="margin-top:4px">Editable en config/db.php</div>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label style="color:var(--muted);font-size:12px;font-weight:900">Ancho de ticket (impresora térmica)</label>
      <input class="select" type="text" value="<?= e((string)($TICKET_ANCHO_MM ?? 80)) ?>mm" disabled style="width:100%;padding:10px 12px;border-radius:14px;margin-top:6px;opacity:.6">
      <div class="xs muted" style="margin-top:4px">Editable en config/db.php ($TICKET_ANCHO_MM)</div>
    </div>

    <div class="field" style="margin-bottom:16px">
      <label style="color:var(--muted);font-size:12px;font-weight:900">Base de datos</label>
      <input class="select" type="text" value="<?= e($dbInfo) ?>" disabled style="width:100%;padding:10px 12px;border-radius:14px;margin-top:6px;opacity:.6">
    </div>

    <div class="hr"></div>
    <div class="xs muted">Versión del sistema: <b>1.0-beta</b></div>
  </div>
</div>

<div class="hr"></div>

<!-- USUARIOS -->
<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Usuarios del sistema</div>
      <span class="pill" id="usrCount">—</span>
    </div>
    <div class="table-toolbar__right">
      <button class="btn" id="btnNuevoUsr" type="button">+ Nuevo usuario</button>
    </div>
  </div>

  <table class="table" id="usrTable">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Usuario</th>
        <th>Rol</th>
        <th>Último acceso</th>
        <th class="num">Estado</th>
        <th class="num">Acciones</th>
      </tr>
    </thead>
    <tbody id="usrTbody">
      <tr><td colspan="6" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</div>

<div class="hr"></div>

<!-- EMPLEADOS -->
<div class="table-wrap">
  <div class="table-toolbar">
    <div class="table-toolbar__left">
      <div class="table-toolbar__title">Empleados</div>
      <span class="pill" id="empCount">—</span>
    </div>
    <div class="table-toolbar__right">
      <button class="btn" id="btnNuevoEmp" type="button">+ Nuevo empleado</button>
    </div>
  </div>

  <table class="table" id="empTable">
    <thead>
      <tr>
        <th>Nombre</th>
        <th>Puesto</th>
        <th>Teléfono</th>
        <th class="num">Sueldo base</th>
        <th class="num">Estado</th>
        <th class="num">Acciones</th>
      </tr>
    </thead>
    <tbody id="empTbody">
      <tr><td colspan="6" class="muted">Cargando…</td></tr>
    </tbody>
  </table>
</div>

<!-- MODAL: nuevo/editar usuario -->
<div class="modal" id="modalUsuario" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close="modalUsuario"></div>
  <div class="modal__dialog" style="max-width:520px" role="dialog">
    <div class="modal__header">
      <div><h3 class="modal__title" id="usrModalTitle">Nuevo usuario</h3></div>
      <button class="modal__close" type="button" data-modal-close="modalUsuario">✕</button>
    </div>
    <div class="modal__body">
      <input type="hidden" id="usrId" value="0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field" style="grid-column:1/-1">
          <label>Nombre completo *</label>
          <input class="select" id="usrNombre" type="text" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Usuario *</label>
          <input class="select" id="usrUsuario" type="text" placeholder="ej. jperez" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Rol *</label>
          <select class="select" id="usrRol" style="width:100%;padding:10px 12px;border-radius:14px">
            <option value="">Cargando…</option>
          </select>
        </div>
        <div class="field">
          <label>Email</label>
          <input class="select" id="usrEmail" type="email" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Teléfono</label>
          <input class="select" id="usrTel" type="tel" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Contraseña <span id="usrPassHint" class="xs muted"></span></label>
          <input class="select" id="usrPassword" type="password" placeholder="Mínimo 6 caracteres" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Estado</label>
          <select class="select" id="usrActivo" style="width:100%;padding:10px 12px;border-radius:14px">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalUsuario">Cancelar</button>
      <button class="btn" id="btnGuardarUsr" type="button">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL: nuevo/editar empleado -->
<div class="modal" id="modalEmpleado" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close="modalEmpleado"></div>
  <div class="modal__dialog" style="max-width:520px" role="dialog">
    <div class="modal__header">
      <div><h3 class="modal__title" id="empModalTitle">Nuevo empleado</h3></div>
      <button class="modal__close" type="button" data-modal-close="modalEmpleado">✕</button>
    </div>
    <div class="modal__body">
      <input type="hidden" id="empId" value="0">
      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field" style="grid-column:1/-1">
          <label>Nombre completo *</label>
          <input class="select" id="empNombre" type="text" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Puesto</label>
          <input class="select" id="empPuesto" type="text" placeholder="Mecánico, Cajero…" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Sueldo base</label>
          <input class="select" id="empSueldo" type="number" min="0" step="0.01" value="0" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Teléfono</label>
          <input class="select" id="empTel" type="tel" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Email</label>
          <input class="select" id="empEmail" type="email" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Fecha de contratación</label>
          <input class="select" id="empFechaContratacion" type="date" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Estado</label>
          <select class="select" id="empActivo" style="width:100%;padding:10px 12px;border-radius:14px">
            <option value="1">Activo</option>
            <option value="0">Inactivo</option>
          </select>
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Notas</label>
          <textarea class="select" id="empNotas" rows="2" style="width:100%;padding:10px 12px;border-radius:14px;resize:none"></textarea>
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalEmpleado">Cancelar</button>
      <button class="btn" id="btnGuardarEmp" type="button">Guardar</button>
    </div>
  </div>
</div>

<!-- MODAL: nómina de un empleado -->
<div class="modal" id="modalNomina" aria-hidden="true">
  <div class="modal__backdrop" data-modal-close="modalNomina"></div>
  <div class="modal__dialog" style="max-width:640px" role="dialog">
    <div class="modal__header">
      <div>
        <h3 class="modal__title">Nómina</h3>
        <p class="modal__subtitle" id="nomEmpleadoNombre"></p>
      </div>
      <button class="modal__close" type="button" data-modal-close="modalNomina">✕</button>
    </div>
    <div class="modal__body" style="max-height:64vh;overflow:auto">
      <input type="hidden" id="nomEmpleadoId" value="0">

      <table class="table" id="nomTable">
        <thead>
          <tr>
            <th>Periodo</th>
            <th class="num">Monto</th>
            <th>Pago</th>
            <th>Método</th>
            <th>Notas</th>
            <th class="num">—</th>
          </tr>
        </thead>
        <tbody id="nomTbody">
          <tr><td colspan="6" class="muted">Cargando…</td></tr>
        </tbody>
      </table>

      <div class="hr"></div>
      <div style="font-weight:800;margin-bottom:8px">Registrar pago</div>

      <div style="display:grid;grid-template-columns:1fr 1fr;gap:12px">
        <div class="field">
          <label>Periodo — inicio</label>
          <input class="select" id="nomPeriodoInicio" type="date" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Periodo — fin</label>
          <input class="select" id="nomPeriodoFin" type="date" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Monto *</label>
          <input class="select" id="nomMonto" type="number" min="0" step="0.01" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Fecha de pago</label>
          <input class="select" id="nomFechaPago" type="date" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
        <div class="field">
          <label>Método</label>
          <select class="select" id="nomMetodo" style="width:100%;padding:10px 12px;border-radius:14px">
            <option value="efectivo">Efectivo</option>
            <option value="transferencia">Transferencia</option>
            <option value="tarjeta">Tarjeta</option>
          </select>
        </div>
        <div class="field" style="grid-column:1/-1">
          <label>Notas</label>
          <input class="select" id="nomNotas" type="text" placeholder="Opcional" style="width:100%;padding:10px 12px;border-radius:14px">
        </div>
      </div>
    </div>
    <div class="modal__footer">
      <button class="btn btn--ghost" type="button" data-modal-close="modalNomina">Cerrar</button>
      <button class="btn" id="btnRegistrarPago" type="button">Registrar pago</button>
    </div>
  </div>
</div>

<script>
(() => {
  const esc = s => String(s ?? '').replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;');
  const $ = id => document.getElementById(id);
  const fmtMoney = n => `$${Number(n||0).toFixed(2)}`;
  const fmtFecha = s => (s||'').slice(0,10);

  // ══════════════════ USUARIOS ══════════════════
  let USUARIOS = [], ROLES = [];

  async function cargarRoles() {
    const r = await fetch('php/api/roles/list.php');
    const j = await r.json();
    ROLES = j.ok ? (j.data || []) : [];
    const sel = $('usrRol');
    sel.innerHTML = ROLES.map(rl => `<option value="${rl.id}">${esc(rl.nombre)}</option>`).join('');
  }

  async function cargarUsuarios() {
    $('usrTbody').innerHTML = '<tr><td colspan="6" class="muted">Cargando…</td></tr>';
    try {
      const r = await fetch('php/api/usuarios/list.php');
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudieron cargar usuarios');
      USUARIOS = j.data || [];
      renderUsuarios();
    } catch (e) {
      $('usrTbody').innerHTML = `<tr><td colspan="6" class="muted">Error: ${esc(e.message||e)}</td></tr>`;
    }
  }

  function renderUsuarios() {
    $('usrCount').textContent = `${USUARIOS.length} usuarios`;
    if (!USUARIOS.length) {
      $('usrTbody').innerHTML = '<tr><td colspan="6" class="muted">Sin usuarios.</td></tr>';
      return;
    }
    $('usrTbody').innerHTML = USUARIOS.map(u => {
      const activo = (u.activo == 1 || u.activo === '1');
      return `
      <tr>
        <td><b>${esc(u.nombre_completo)}</b></td>
        <td>@${esc(u.usuario)}</td>
        <td>${esc(u.rol_nombre||'—')}</td>
        <td style="font-size:12px;color:var(--muted)">${u.ultimo_acceso ? fmtFecha(u.ultimo_acceso) : 'Nunca'}</td>
        <td class="num"><span class="tag ${activo?'tag--ok':'tag--bad'}">${activo?'Activo':'Inactivo'}</span></td>
        <td class="num">
          <div class="row-actions">
            <button class="btn-sm" data-usr-edit="${u.id}">Editar</button>
            <button class="btn-sm" data-usr-del="${u.id}">Eliminar</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  function limpiarFormUsr() {
    $('usrId').value = '0';
    $('usrModalTitle').textContent = 'Nuevo usuario';
    $('usrPassHint').textContent = '(requerida)';
    ['usrNombre','usrUsuario','usrEmail','usrTel','usrPassword'].forEach(id => { $(id).value = ''; });
    $('usrActivo').value = '1';
    if (ROLES.length) $('usrRol').value = String(ROLES[0].id);
  }

  $('btnNuevoUsr').addEventListener('click', () => {
    limpiarFormUsr();
    modalOpen('modalUsuario');
    setTimeout(() => $('usrNombre')?.focus(), 60);
  });

  $('usrTbody').addEventListener('click', (e) => {
    const btnEdit = e.target.closest('[data-usr-edit]');
    if (btnEdit) {
      const id = Number(btnEdit.getAttribute('data-usr-edit'));
      const u = USUARIOS.find(x => Number(x.id) === id);
      if (!u) return;
      $('usrId').value = u.id;
      $('usrModalTitle').textContent = 'Editar usuario';
      $('usrPassHint').textContent = '(deja en blanco para no cambiarla)';
      $('usrNombre').value = u.nombre_completo || '';
      $('usrUsuario').value = u.usuario || '';
      $('usrEmail').value = u.email || '';
      $('usrTel').value = u.telefono || '';
      $('usrPassword').value = '';
      $('usrRol').value = String(u.rol_id);
      $('usrActivo').value = String(Number(u.activo ?? 1));
      modalOpen('modalUsuario');
      return;
    }
    const btnDel = e.target.closest('[data-usr-del]');
    if (btnDel) eliminarUsuario(Number(btnDel.getAttribute('data-usr-del')));
  });

  $('btnGuardarUsr').addEventListener('click', async () => {
    const id = Number($('usrId').value || 0);
    const nombre = $('usrNombre').value.trim();
    const usuario = $('usrUsuario').value.trim();
    const rol_id = Number($('usrRol').value || 0);
    const password = $('usrPassword').value;

    if (!nombre || !usuario) { toast('Faltan datos', 'Nombre y usuario son requeridos.', {icon:'⚠️'}); return; }
    if (!rol_id) { toast('Falta rol', 'Selecciona un rol.', {icon:'⚠️'}); return; }
    if (id === 0 && password.length < 6) { toast('Contraseña corta', 'Mínimo 6 caracteres.', {icon:'⚠️'}); return; }
    if (password && password.length < 6) { toast('Contraseña corta', 'Mínimo 6 caracteres.', {icon:'⚠️'}); return; }

    const body = {
      id, nombre_completo: nombre, usuario, rol_id,
      email: $('usrEmail').value.trim(),
      telefono: $('usrTel').value.trim(),
      activo: Number($('usrActivo').value),
      password,
    };
    const url = id > 0 ? 'php/api/usuarios/update.php' : 'php/api/usuarios/create.php';
    try {
      const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo guardar');
      modalClose('modalUsuario');
      toast('Guardado', nombre, {icon:'👤'});
      await cargarUsuarios();
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  });

  async function eliminarUsuario(id) {
    const ok = window.confirm('¿Eliminar este usuario? Si ya tiene actividad registrada, se marcará como inactivo en vez de borrarse.');
    if (!ok) return;
    try {
      const r = await fetch('php/api/usuarios/delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo eliminar');
      toast('Listo', j.mode === 'soft' ? 'Usuario marcado como inactivo.' : 'Usuario eliminado.', {icon:'✓'});
      await cargarUsuarios();
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  }

  // ══════════════════ EMPLEADOS ══════════════════
  let EMPLEADOS = [];

  async function cargarEmpleados() {
    $('empTbody').innerHTML = '<tr><td colspan="6" class="muted">Cargando…</td></tr>';
    try {
      const r = await fetch('php/api/empleados/list.php');
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudieron cargar empleados');
      EMPLEADOS = j.data || [];
      renderEmpleados();
    } catch (e) {
      $('empTbody').innerHTML = `<tr><td colspan="6" class="muted">Error: ${esc(e.message||e)}</td></tr>`;
    }
  }

  function renderEmpleados() {
    $('empCount').textContent = `${EMPLEADOS.length} empleados`;
    if (!EMPLEADOS.length) {
      $('empTbody').innerHTML = '<tr><td colspan="6" class="muted">Sin empleados registrados.</td></tr>';
      return;
    }
    $('empTbody').innerHTML = EMPLEADOS.map(emp => {
      const activo = (emp.activo == 1 || emp.activo === '1');
      return `
      <tr>
        <td><b>${esc(emp.nombre_completo)}</b></td>
        <td>${esc(emp.puesto||'—')}</td>
        <td>${esc(emp.telefono||'—')}</td>
        <td class="num">${fmtMoney(emp.sueldo_base)}</td>
        <td class="num"><span class="tag ${activo?'tag--ok':'tag--bad'}">${activo?'Activo':'Inactivo'}</span></td>
        <td class="num">
          <div class="row-actions">
            <button class="btn-sm" data-emp-edit="${emp.id}">Editar</button>
            <button class="btn-sm" data-emp-nom="${emp.id}">Nómina</button>
            <button class="btn-sm" data-emp-del="${emp.id}">Eliminar</button>
          </div>
        </td>
      </tr>`;
    }).join('');
  }

  function limpiarFormEmp() {
    $('empId').value = '0';
    $('empModalTitle').textContent = 'Nuevo empleado';
    ['empNombre','empPuesto','empTel','empEmail','empFechaContratacion','empNotas'].forEach(id => { $(id).value = ''; });
    $('empSueldo').value = '0';
    $('empActivo').value = '1';
  }

  $('btnNuevoEmp').addEventListener('click', () => {
    limpiarFormEmp();
    modalOpen('modalEmpleado');
    setTimeout(() => $('empNombre')?.focus(), 60);
  });

  $('empTbody').addEventListener('click', (e) => {
    const btnEdit = e.target.closest('[data-emp-edit]');
    if (btnEdit) {
      const id = Number(btnEdit.getAttribute('data-emp-edit'));
      const emp = EMPLEADOS.find(x => Number(x.id) === id);
      if (!emp) return;
      $('empId').value = emp.id;
      $('empModalTitle').textContent = 'Editar empleado';
      $('empNombre').value = emp.nombre_completo || '';
      $('empPuesto').value = emp.puesto || '';
      $('empSueldo').value = Number(emp.sueldo_base || 0);
      $('empTel').value = emp.telefono || '';
      $('empEmail').value = emp.email || '';
      $('empFechaContratacion').value = fmtFecha(emp.fecha_contratacion);
      $('empActivo').value = String(Number(emp.activo ?? 1));
      $('empNotas').value = emp.notas || '';
      modalOpen('modalEmpleado');
      return;
    }
    const btnNom = e.target.closest('[data-emp-nom]');
    if (btnNom) {
      const id = Number(btnNom.getAttribute('data-emp-nom'));
      const emp = EMPLEADOS.find(x => Number(x.id) === id);
      abrirNomina(id, emp ? emp.nombre_completo : '');
      return;
    }
    const btnDel = e.target.closest('[data-emp-del]');
    if (btnDel) eliminarEmpleado(Number(btnDel.getAttribute('data-emp-del')));
  });

  $('btnGuardarEmp').addEventListener('click', async () => {
    const id = Number($('empId').value || 0);
    const nombre = $('empNombre').value.trim();
    if (!nombre) { toast('Falta nombre', 'El nombre completo es requerido.', {icon:'⚠️'}); return; }

    const body = {
      id, nombre_completo: nombre,
      puesto: $('empPuesto').value.trim(),
      sueldo_base: Number($('empSueldo').value || 0),
      telefono: $('empTel').value.trim(),
      email: $('empEmail').value.trim(),
      fecha_contratacion: $('empFechaContratacion').value || null,
      activo: Number($('empActivo').value),
      notas: $('empNotas').value.trim(),
    };
    const url = id > 0 ? 'php/api/empleados/update.php' : 'php/api/empleados/create.php';
    try {
      const r = await fetch(url, { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo guardar');
      modalClose('modalEmpleado');
      toast('Guardado', nombre, {icon:'🧑‍🔧'});
      await cargarEmpleados();
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  });

  async function eliminarEmpleado(id) {
    const ok = window.confirm('¿Eliminar este empleado? Se marcará como inactivo (su historial de nómina se conserva).');
    if (!ok) return;
    try {
      const r = await fetch('php/api/empleados/delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo eliminar');
      toast('Listo', 'Empleado marcado como inactivo.', {icon:'✓'});
      await cargarEmpleados();
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  }

  // ══════════════════ NÓMINA ══════════════════
  function fechaHoy() {
    const d = new Date();
    return `${d.getFullYear()}-${String(d.getMonth()+1).padStart(2,'0')}-${String(d.getDate()).padStart(2,'0')}`;
  }
  function inicioSemanaActual() {
    const d = new Date();
    const day = d.getDay(); // 0=domingo
    const diff = (day === 0 ? -6 : 1) - day; // lunes de esta semana
    const monday = new Date(d);
    monday.setDate(d.getDate() + diff);
    return `${monday.getFullYear()}-${String(monday.getMonth()+1).padStart(2,'0')}-${String(monday.getDate()).padStart(2,'0')}`;
  }

  window.abrirNomina = async (empleadoId, nombre) => {
    $('nomEmpleadoId').value = empleadoId;
    $('nomEmpleadoNombre').textContent = nombre || '';
    const emp = EMPLEADOS.find(x => Number(x.id) === empleadoId);
    $('nomPeriodoInicio').value = inicioSemanaActual();
    $('nomPeriodoFin').value = fechaHoy();
    $('nomFechaPago').value = fechaHoy();
    $('nomMonto').value = emp ? Number(emp.sueldo_base || 0) : '';
    $('nomMetodo').value = 'efectivo';
    $('nomNotas').value = '';
    modalOpen('modalNomina');
    await cargarNomina(empleadoId);
  };

  async function cargarNomina(empleadoId) {
    $('nomTbody').innerHTML = '<tr><td colspan="6" class="muted">Cargando…</td></tr>';
    try {
      const r = await fetch(`php/api/nomina/list.php?empleado_id=${empleadoId}`);
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo cargar la nómina');
      const rows = j.data || [];
      if (!rows.length) {
        $('nomTbody').innerHTML = '<tr><td colspan="6" class="muted">Sin pagos registrados.</td></tr>';
        return;
      }
      $('nomTbody').innerHTML = rows.map(p => `
        <tr>
          <td style="font-size:12px">${fmtFecha(p.periodo_inicio)} → ${fmtFecha(p.periodo_fin)}</td>
          <td class="num">${fmtMoney(p.monto)}</td>
          <td style="font-size:12px">${fmtFecha(p.fecha_pago)}</td>
          <td>${esc(p.metodo_pago||'—')}</td>
          <td style="font-size:12px;color:var(--muted)">${esc(p.notas||'—')}</td>
          <td class="num"><button class="btn-sm" data-nom-del="${p.id}">Eliminar</button></td>
        </tr>`).join('');
    } catch (e) {
      $('nomTbody').innerHTML = `<tr><td colspan="6" class="muted">Error: ${esc(e.message||e)}</td></tr>`;
    }
  }

  $('nomTbody').addEventListener('click', async (e) => {
    const btnDel = e.target.closest('[data-nom-del]');
    if (!btnDel) return;
    const id = Number(btnDel.getAttribute('data-nom-del'));
    const ok = window.confirm('¿Eliminar este pago de nómina?');
    if (!ok) return;
    try {
      const r = await fetch('php/api/nomina/delete.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify({ id }) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo eliminar');
      toast('Listo', 'Pago eliminado.', {icon:'✓'});
      await cargarNomina(Number($('nomEmpleadoId').value));
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  });

  $('btnRegistrarPago').addEventListener('click', async () => {
    const empleado_id = Number($('nomEmpleadoId').value || 0);
    const monto = Number($('nomMonto').value || 0);
    if (!monto || monto <= 0) { toast('Falta monto', 'El monto debe ser mayor a cero.', {icon:'⚠️'}); return; }

    const body = {
      empleado_id,
      periodo_inicio: $('nomPeriodoInicio').value,
      periodo_fin: $('nomPeriodoFin').value,
      monto,
      fecha_pago: $('nomFechaPago').value,
      metodo_pago: $('nomMetodo').value,
      notas: $('nomNotas').value.trim(),
    };
    try {
      const r = await fetch('php/api/nomina/create.php', { method:'POST', headers:{'Content-Type':'application/json'}, body: JSON.stringify(body) });
      const j = await r.json();
      if (!j.ok) throw new Error(j.error || 'No se pudo registrar el pago');
      toast('Pago registrado', fmtMoney(monto), {icon:'💵'});
      await cargarNomina(empleado_id);
    } catch (e) { toast('Error', String(e.message||e), {icon:'⚠️'}); }
  });

  // ══════════════════ Inicializar ══════════════════
  (async () => {
    await cargarRoles();
    await cargarUsuarios();
    await cargarEmpleados();
  })();
})();
</script>
