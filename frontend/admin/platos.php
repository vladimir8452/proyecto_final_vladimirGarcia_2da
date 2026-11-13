<?php
$pagina = 'Catálogo de Platos';
require_once __DIR__ . '/layout.php';
?>
<div class="card">
  <div class="card-header" style="display:flex;justify-content:space-between;align-items:center;gap:1rem;flex-wrap:wrap;">
    <h3>🍽️ Platos</h3>
    <div style="display:flex;gap:.5rem;align-items:center;">
      <select id="filtroTipo" class="form-control" style="width:auto;" onchange="cargarPlatos()">
        <option value="">Todos los tipos</option>
        <option value="SOPA">Sopas</option>
        <option value="PRINCIPAL">Platos principales</option>
        <option value="POSTRE">Postres</option>
      </select>
      <button class="btn btn-primary" onclick="nuevoPlato()">+ Nuevo plato</button>
    </div>
  </div>
  <div class="card-body">
    <div id="alertaPlatos"></div>
    <table class="table">
      <thead>
        <tr><th>Nombre</th><th>Tipo</th><th>Descripción</th><th>Estado</th><th>Acciones</th></tr>
      </thead>
      <tbody id="tbodyPlatos"></tbody>
    </table>
  </div>
</div>

<div class="modal-overlay" id="modalPlato">
  <div class="modal">
    <div class="modal-header">
      <h4 id="tituloModalPlato">Registrar plato</h4>
      <button class="btn-close" onclick="closeModal('modalPlato')">×</button>
    </div>
    <div class="modal-body">
      <div id="alertaModalPlato"></div>
      <input type="hidden" id="platoId">
      <div class="form-group">
        <label class="form-label">Nombre *</label>
        <input id="platoNombre" class="form-control" maxlength="180">
      </div>
      <div class="form-group">
        <label class="form-label">Tipo *</label>
        <select id="platoTipo" class="form-control">
          <option value="SOPA">Sopa</option>
          <option value="PRINCIPAL">Plato principal</option>
          <option value="POSTRE">Postre</option>
        </select>
      </div>
      <div class="form-group">
        <label class="form-label">Descripción</label>
        <input id="platoDescripcion" class="form-control" maxlength="255">
      </div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modalPlato')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarPlato()">Guardar</button>
    </div>
  </div>
</div>

<script>
const API_PLATOS = '/backend/api/platos.php';
const ETIQUETA_TIPO = { SOPA: 'Sopa', PRINCIPAL: 'Plato principal', POSTRE: 'Postre' };
let platosCargados = [];

function escHtml(str) {
  const d = document.createElement('div');
  d.textContent = str ?? '';
  return d.innerHTML;
}

async function cargarPlatos() {
  const r = await apiGet(API_PLATOS, { action: 'listar', tipo: document.getElementById('filtroTipo').value });
  const tbody = document.getElementById('tbodyPlatos');
  if (!r.success) {
    tbody.innerHTML = '<tr><td colspan="5">' + escHtml(r.message || 'No se pudo cargar.') + '</td></tr>';
    return;
  }
  platosCargados = r.data;
  if (!platosCargados.length) {
    tbody.innerHTML = '<tr><td colspan="5" style="text-align:center;">Aún no hay platos registrados.</td></tr>';
    return;
  }
  tbody.innerHTML = platosCargados.map(p => `
    <tr>
      <td><strong>${escHtml(p.nombre)}</strong></td>
      <td><span class="badge badge-info">${ETIQUETA_TIPO[p.tipo]}</span></td>
      <td>${escHtml(p.descripcion || '—')}</td>
      <td>${p.activo ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'}</td>
      <td>
        <div style="display:flex;gap:.3rem;">
          <button class="btn btn-secondary btn-sm btn-icon" title="Editar" onclick="editarPlato(${p.id})">✏️</button>
          ${p.activo
            ? `<button class="btn btn-danger btn-sm" onclick="cambiarEstado(${p.id}, 'desactivar')">Desactivar</button>`
            : `<button class="btn btn-secondary btn-sm" onclick="cambiarEstado(${p.id}, 'activar')">Activar</button>`}
        </div>
      </td>
    </tr>`).join('');
}

function nuevoPlato() {
  document.getElementById('tituloModalPlato').textContent = 'Registrar plato';
  ['platoId', 'platoNombre', 'platoDescripcion'].forEach(id => document.getElementById(id).value = '');
  document.getElementById('platoTipo').value = 'SOPA';
  document.getElementById('alertaModalPlato').innerHTML = '';
  openModal('modalPlato');
}

function editarPlato(id) {
  const p = platosCargados.find(x => x.id === id);
  if (!p) return;
  document.getElementById('tituloModalPlato').textContent = 'Modificar plato';
  document.getElementById('platoId').value = p.id;
  document.getElementById('platoNombre').value = p.nombre;
  document.getElementById('platoTipo').value = p.tipo;
  document.getElementById('platoDescripcion').value = p.descripcion || '';
  document.getElementById('alertaModalPlato').innerHTML = '';
  openModal('modalPlato');
}

async function guardarPlato() {
  const id = document.getElementById('platoId').value;
  const r = await apiPost(API_PLATOS, {
    action: id ? 'modificar' : 'registrar',
    id: id,
    nombre: document.getElementById('platoNombre').value,
    tipo: document.getElementById('platoTipo').value,
    descripcion: document.getElementById('platoDescripcion').value,
  });
  if (!r.success) {
    document.getElementById('alertaModalPlato').innerHTML = '<div class="alert alert-danger">' + escHtml(r.message) + '</div>';
    return;
  }
  closeModal('modalPlato');
  Toast.success(r.message);
  cargarPlatos();
}

async function cambiarEstado(id, accion) {
  const r = await apiPost(API_PLATOS, { action: accion, id: id });
  if (!r.success) {
    document.getElementById('alertaPlatos').innerHTML = '<div class="alert alert-danger">' + escHtml(r.message) + '</div>';
    return;
  }
  document.getElementById('alertaPlatos').innerHTML = '';
  Toast.success(r.message);
  cargarPlatos();
}

cargarPlatos();
</script>
<?php require_once __DIR__ . '/layout_end.php'; ?>
