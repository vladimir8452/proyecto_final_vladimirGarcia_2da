<?php
$pagina = 'Gestión de Empleados';
require_once __DIR__ . '/layout.php';
?>

<div class="card">
  <div class="card-header">
    <h3>👥 Empleados Registrados</h3>
    <button class="btn btn-primary" onclick="openModal('modalNuevoEmpleado')">
      + Nuevo Empleado
    </button>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-responsive">
      <table class="table" id="tablaEmpleados">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th>
            <th>Código</th>
            <th>Correo</th>
            <th>Celular</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Acciones</th>
          </tr>
        </thead>
        <tbody id="tbodyEmpleados">
          <tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">
            <div class="spinner" style="margin:0 auto .5rem;"></div> Cargando...
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<!-- Modal Nuevo Empleado -->
<div class="modal-overlay" id="modalNuevoEmpleado">
  <div class="modal">
    <div class="modal-header">
      <h4>Registrar Empleado</h4>
      <button class="btn-close" onclick="closeModal('modalNuevoEmpleado')">×</button>
    </div>
    <div class="modal-body">
      <div id="alertNuevo"></div>
      <form id="formNuevoEmpleado" novalidate>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nombre(s) *</label>
            <input type="text" name="nombre" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Apellido(s) *</label>
            <input type="text" name="apellidos" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Código de Empleado *</label>
            <input type="text" name="codigo_empleado" class="form-control" required placeholder="Ej: EMP-001">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">CI *</label><input type="text" name="ci" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Departamento *</label><input type="text" name="departamento" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Correo electrónico *</label>
            <input type="email" name="correo" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Celular</label>
            <input type="text" name="celular" class="form-control" placeholder="+591 7XXXXXXX">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tipo de Cuenta *</label>
            <select name="tipo_cuenta" class="form-select" required>
              <option value="">Seleccionar...</option>
              <option value="PENSIONADO">Pensionado (15 Bs/almuerzo)</option>
              <option value="NO_PENSIONADO">No Pensionado (18 Bs/almuerzo)</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Contraseña *</label>
            <input type="password" name="contrasena" class="form-control" required minlength="6" placeholder="Mín. 6 caracteres">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modalNuevoEmpleado')">Cancelar</button>
      <button class="btn btn-primary" id="btnGuardarEmpleado" onclick="guardarEmpleado()">Guardar</button>
    </div>
  </div>
</div>

<!-- Modal Editar Empleado -->
<div class="modal-overlay" id="modalEditarEmpleado">
  <div class="modal">
    <div class="modal-header">
      <h4>Editar Empleado</h4>
      <button class="btn-close" onclick="closeModal('modalEditarEmpleado')">×</button>
    </div>
    <div class="modal-body">
      <div id="alertEditar"></div>
      <form id="formEditarEmpleado" novalidate>
        <input type="hidden" name="id" id="editId">
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Nombre completo *</label>
            <input type="text" name="nombre" id="editNombre" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Apellido(s) *</label><input type="text" name="apellidos" id="editApellidos" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Código de Empleado *</label>
            <input type="text" name="codigo_empleado" id="editCodigo" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">CI *</label><input type="text" name="ci" id="editCi" class="form-control" required>
          </div>
          <div class="form-group"><label class="form-label">Departamento *</label><input type="text" name="departamento" id="editDepartamento" class="form-control" required>
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Correo electrónico *</label>
            <input type="email" name="correo" id="editCorreo" class="form-control" required>
          </div>
          <div class="form-group">
            <label class="form-label">Celular</label>
            <input type="text" name="celular" id="editCelular" class="form-control">
          </div>
        </div>
        <div class="form-row">
          <div class="form-group">
            <label class="form-label">Tipo de Cuenta *</label>
            <select name="tipo_cuenta" id="editTipo" class="form-select" required>
              <option value="PENSIONADO">Pensionado</option>
              <option value="NO_PENSIONADO">No Pensionado</option>
            </select>
          </div>
          <div class="form-group">
            <label class="form-label">Nueva Contraseña <span class="text-muted">(opcional)</span></label>
            <input type="password" name="contrasena" class="form-control" minlength="6" placeholder="Dejar vacío para no cambiar">
          </div>
        </div>
      </form>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modalEditarEmpleado')">Cancelar</button>
      <button class="btn btn-primary" id="btnActualizarEmpleado" onclick="actualizarEmpleado()">Actualizar</button>
    </div>
  </div>
</div>

<!-- Modal Familiares -->
<div class="modal-overlay" id="modalFamiliares">
  <div class="modal" style="max-width:600px;">
    <div class="modal-header">
      <h4>Familiares de <span id="familiarTitularNombre"></span></h4>
      <button class="btn-close" onclick="closeModal('modalFamiliares')">×</button>
    </div>
    <div class="modal-body">
      <div id="alertFamiliar"></div>
      <div style="display:flex;gap:.5rem;margin-bottom:1rem;">
        <input type="hidden" id="familiarTitularId">
        <input type="text" id="nuevoFamiliarNombre" class="form-control" placeholder="Nombre del familiar">
        <button class="btn btn-success" onclick="agregarFamiliar()" style="white-space:nowrap;">+ Agregar</button>
      </div>
      <div id="listaFamiliares"></div>
    </div>
  </div>
</div>

<script>
let empleados = [];

async function cargarEmpleados() {
  const res = await apiGet('/backend/api/empleados.php', { action: 'listar' });
  empleados = res.data || [];
  renderTabla();
}

function renderTabla() {
  const tbody = document.getElementById('tbodyEmpleados');
  if (!empleados.length) {
    tbody.innerHTML = '<tr><td colspan="8" class="text-center text-muted" style="padding:2rem;">No hay empleados registrados.</td></tr>';
    return;
  }
  tbody.innerHTML = empleados.map((e, i) => `
    <tr>
      <td>${i+1}</td>
      <td><strong>${escHtml(e.nombre)}</strong></td>
      <td><span class="badge badge-muted">${escHtml(e.codigo_empleado || '—')}</span></td>
      <td>${escHtml(e.correo)}</td>
      <td>${escHtml(e.celular || '—')}</td>
      <td>
        ${e.tipo_cuenta === 'PENSIONADO'
          ? '<span class="badge badge-info">Pensionado</span>'
          : '<span class="badge badge-warning">No Pensionado</span>'}
      </td>
      <td>
        ${e.activo == 1
          ? '<span class="badge badge-success">Activo</span>'
          : '<span class="badge badge-danger">Inactivo</span>'}
      </td>
      <td>
        <div style="display:flex;gap:.3rem;">
          <button class="btn btn-secondary btn-sm btn-icon" title="Editar" onclick="abrirEditar(${e.id})">✏️</button>
          <button class="btn btn-primary btn-sm" title="Familiares" onclick="abrirFamiliares(${e.id},'${escHtml(e.nombre)}')"
            ${e.tipo_cuenta !== 'PENSIONADO' ? 'style="opacity:.4;cursor:not-allowed;" disabled' : ''}>
            👨‍👩‍👧
          </button>
          ${e.activo == 1
            ? `<button class="btn btn-danger btn-sm btn-icon" title="Desactivar" onclick="cambiarEstado(${e.id},0)">🚫</button>`
            : `<button class="btn btn-success btn-sm btn-icon" title="Activar" onclick="cambiarEstado(${e.id},1)">✓</button>`}
        </div>
      </td>
    </tr>
  `).join('');
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

async function guardarEmpleado() {
  const form = document.getElementById('formNuevoEmpleado');
  const btn  = document.getElementById('btnGuardarEmpleado');
  const alertEl = document.getElementById('alertNuevo');
  alertEl.innerHTML = '';

  const data = Object.fromEntries(new FormData(form));
  data.action = 'registrar';

  btnLoading(btn, true);
  const res = await apiPost('/backend/api/empleados.php', data);

  if (res.success) {
    Toast.success(res.message);
    closeModal('modalNuevoEmpleado');
    form.reset();
    cargarEmpleados();
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
  }
  btnLoading(btn, false);
}

function abrirEditar(id) {
  const e = empleados.find(x => x.id == id);
  if (!e) return;
  document.getElementById('editId').value      = e.id;
  document.getElementById('editNombre').value  = e.nombre;
  document.getElementById('editApellidos').value = e.apellidos || '';
  document.getElementById('editCi').value = e.ci || '';
  document.getElementById('editDepartamento').value = e.departamento || '';
  document.getElementById('editCodigo').value  = e.codigo_empleado || '';
  document.getElementById('editCorreo').value  = e.correo;
  document.getElementById('editCelular').value = e.celular || '';
  document.getElementById('editTipo').value    = e.tipo_cuenta;
  document.getElementById('alertEditar').innerHTML = '';
  openModal('modalEditarEmpleado');
}

async function actualizarEmpleado() {
  const form = document.getElementById('formEditarEmpleado');
  const btn  = document.getElementById('btnActualizarEmpleado');
  const alertEl = document.getElementById('alertEditar');
  alertEl.innerHTML = '';

  const data = Object.fromEntries(new FormData(form));
  data.action = 'actualizar';

  btnLoading(btn, true);
  const res = await apiPost('/backend/api/empleados.php', data);

  if (res.success) {
    Toast.success(res.message);
    closeModal('modalEditarEmpleado');
    cargarEmpleados();
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
  }
  btnLoading(btn, false);
}

async function cambiarEstado(id, activo) {
  const msg = activo ? '¿Activar este empleado?' : '¿Desactivar este empleado?';
  if (!confirmar(msg)) return;
  const res = await apiPost('/backend/api/empleados.php', { action: 'cambiar_estado', id, activo });
  if (res.success) { Toast.success(res.message); cargarEmpleados(); }
  else Toast.error(res.message);
}

async function abrirFamiliares(titularId, nombre) {
  document.getElementById('familiarTitularId').value   = titularId;
  document.getElementById('familiarTitularNombre').textContent = nombre;
  document.getElementById('alertFamiliar').innerHTML   = '';
  document.getElementById('nuevoFamiliarNombre').value = '';
  openModal('modalFamiliares');
  await cargarFamiliaresModal(titularId);
}

async function cargarFamiliaresModal(titularId) {
  const lista = document.getElementById('listaFamiliares');
  lista.innerHTML = '<p class="text-muted text-center">Cargando...</p>';
  const res = await apiGet('/backend/api/familiares.php', { action: 'listar', titular_id: titularId });
  const fams = res.data || [];
  if (!fams.length) {
    lista.innerHTML = '<p class="text-muted text-center" style="padding:1rem;">Sin familiares registrados.</p>';
    return;
  }
  lista.innerHTML = fams.map(f => `
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.6rem 0;border-bottom:1px solid #eef2f7;">
      <span>${escHtml(f.nombre)} 
        ${f.estado === 'ACTIVO'
          ? '<span class="badge badge-success" style="margin-left:.4rem;">Activo</span>'
          : '<span class="badge badge-danger" style="margin-left:.4rem;">Inactivo</span>'}
      </span>
      ${f.estado === 'ACTIVO'
        ? `<button class="btn btn-danger btn-sm" onclick="desactivarFamiliar(${f.id})">Desactivar</button>`
        : ''}
    </div>
  `).join('');
}

async function agregarFamiliar() {
  const titularId = document.getElementById('familiarTitularId').value;
  const nombre    = document.getElementById('nuevoFamiliarNombre').value.trim();
  const alertEl   = document.getElementById('alertFamiliar');
  alertEl.innerHTML = '';

  if (!nombre) {
    alertEl.innerHTML = '<div class="alert alert-warning">Ingresa el nombre del familiar.</div>';
    return;
  }

  const res = await apiPost('/backend/api/familiares.php', {
    action: 'registrar', nombre, titular_id: titularId
  });

  if (res.success) {
    Toast.success(res.message);
    document.getElementById('nuevoFamiliarNombre').value = '';
    await cargarFamiliaresModal(titularId);
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
  }
}

async function desactivarFamiliar(familiarId) {
  if (!confirmar('¿Desactivar este familiar?')) return;
  const titularId = document.getElementById('familiarTitularId').value;
  const res = await apiPost('/backend/api/familiares.php', {
    action: 'desactivar', familiar_id: familiarId, titular_id: titularId
  });
  if (res.success) { Toast.success(res.message); await cargarFamiliaresModal(titularId); }
  else Toast.error(res.message);
}

// Cargar al inicio
cargarEmpleados();
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
