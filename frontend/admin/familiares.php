<?php
$pagina = 'Gestión de Familiares';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../../backend/config/database.php';

// Traer solo pensionados para el selector
$db = Database::getConnection();
$pensionados = $db->query(
    "SELECT id, nombre, codigo_empleado FROM usuarios WHERE tipo_cuenta='PENSIONADO' AND activo=1 ORDER BY nombre ASC"
)->fetchAll();
?>

<div style="display:grid;grid-template-columns:1fr 1.8fr;gap:1.5rem;">

  <!-- Panel izquierdo: selector titular -->
  <div class="card">
    <div class="card-header"><h3>Seleccionar Titular</h3></div>
    <div class="card-body" style="padding:0;max-height:70vh;overflow-y:auto;">
      <?php if ($pensionados): ?>
        <?php foreach ($pensionados as $p): ?>
          <button class="nav-link w-100" style="text-align:left;border:none;background:none;padding:.75rem 1rem;cursor:pointer;"
                  onclick="seleccionarTitular(<?= $p['id'] ?>, '<?= htmlspecialchars(addslashes($p['nombre'])) ?>')">
            <strong><?= htmlspecialchars($p['nombre']) ?></strong>
            <br><small class="text-muted"><?= htmlspecialchars($p['codigo_empleado'] ?? '—') ?></small>
          </button>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state">
          <div class="empty-icon">👥</div>
          <h4>Sin empleados pensionados</h4>
          <p>Registra empleados pensionados primero.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Panel derecho: familiares del titular -->
  <div class="card">
    <div class="card-header">
      <h3 id="titularNombre">Selecciona un titular</h3>
      <button class="btn btn-primary btn-sm" id="btnAgregar" style="display:none;" onclick="openModal('modalAgregarFam')">
        + Agregar Familiar
      </button>
    </div>
    <div class="card-body" style="padding:0;">
      <div id="familiaresPanelInner">
        <div class="empty-state">
          <div class="empty-icon">👈</div>
          <h4>Selecciona un titular de la lista</h4>
        </div>
      </div>
    </div>
  </div>

</div>

<!-- Modal agregar familiar -->
<div class="modal-overlay" id="modalAgregarFam">
  <div class="modal">
    <div class="modal-header">
      <h4>Agregar Familiar</h4>
      <button class="btn-close" onclick="closeModal('modalAgregarFam')">×</button>
    </div>
    <div class="modal-body">
      <div id="alertAgregarFam"></div>
      <input type="hidden" id="famTitularId">
      <div class="form-group">
        <label class="form-label">Nombre completo del familiar *</label>
        <input type="text" id="famNombre" class="form-control" placeholder="Nombre y apellido">
      </div>
      <div class="form-group"><label class="form-label">CI *</label><input type="text" id="famCi" class="form-control"></div>
      <div class="form-group"><label class="form-label">Parentesco de primer grado *</label><select id="famParentesco" class="form-select"><option value="">Seleccionar...</option><option value="PADRE">Padre</option><option value="MADRE">Madre</option><option value="CONYUGE">Cónyuge</option><option value="HIJO">Hijo/a</option></select></div>
    </div>
    <div class="modal-footer">
      <button class="btn btn-secondary" onclick="closeModal('modalAgregarFam')">Cancelar</button>
      <button class="btn btn-primary" onclick="guardarFamiliar()">Guardar</button>
    </div>
  </div>
</div>

<script>
let titularActualId = null;

function seleccionarTitular(id, nombre) {
  titularActualId = id;
  document.getElementById('titularNombre').textContent = nombre;
  document.getElementById('btnAgregar').style.display  = '';
  document.getElementById('famTitularId').value        = id;
  cargarFamiliares(id);
}

async function cargarFamiliares(titularId) {
  const panel = document.getElementById('familiaresPanelInner');
  panel.innerHTML = '<p class="text-muted text-center" style="padding:1.5rem;">Cargando...</p>';

  const res = await apiGet('/backend/api/familiares.php', { action: 'listar', titular_id: titularId });
  const fams = res.data || [];

  if (!fams.length) {
    panel.innerHTML = `<div class="empty-state">
      <div class="empty-icon">➕</div>
      <h4>Sin familiares registrados</h4>
    </div>`;
    return;
  }

  const activos = fams.filter(f => f.estado === 'ACTIVO').length;

  panel.innerHTML = `
    <div style="padding:.75rem 1rem;background:#f8fafc;border-bottom:1px solid var(--border);font-size:.85rem;">
      <strong>${activos}</strong> familiares activos registrados
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead><tr><th>#</th><th>Nombre</th><th>Estado</th><th>Registrado</th><th>Acciones</th></tr></thead>
        <tbody>
          ${fams.map((f,i) => `
            <tr>
              <td>${i+1}</td>
              <td><strong>${escHtml(f.nombre)}</strong></td>
              <td>${f.estado === 'ACTIVO'
                ? '<span class="badge badge-success">Activo</span>'
                : '<span class="badge badge-danger">Inactivo</span>'}</td>
              <td style="font-size:.8rem;color:var(--muted);">${formatDateShort(f.created_at?.split(' ')[0])}</td>
              <td>
                ${f.estado === 'ACTIVO'
                  ? `<button class="btn btn-danger btn-sm" onclick="desactivar(${f.id})">Desactivar</button>`
                  : '<span class="text-muted" style="font-size:.8rem;">Inactivo</span>'}
              </td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function guardarFamiliar() {
  const nombre = document.getElementById('famNombre').value.trim();
  const titularId = document.getElementById('famTitularId').value;
  const ci = document.getElementById('famCi').value.trim();
  const parentesco = document.getElementById('famParentesco').value;
  const alertEl = document.getElementById('alertAgregarFam');
  alertEl.innerHTML = '';

  if (!nombre || !ci || !parentesco) {
    alertEl.innerHTML = '<div class="alert alert-warning">Completa nombre, CI y parentesco.</div>'; 
    return;
  }

  const res = await apiPost('/backend/api/familiares.php', {
    action: 'registrar', nombre, ci, parentesco, titular_id: titularId
  });

  if (res.success) {
    Toast.success(res.message);
    closeModal('modalAgregarFam');
    document.getElementById('famNombre').value = '';
    document.getElementById('famCi').value = ''; document.getElementById('famParentesco').value = '';
    cargarFamiliares(titularId);
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
  }
}

async function desactivar(familiarId) {
  if (!confirmar('¿Desactivar este familiar?')) return;
  const res = await apiPost('/backend/api/familiares.php', {
    action: 'desactivar', familiar_id: familiarId, titular_id: titularActualId
  });
  if (res.success) { Toast.success(res.message); cargarFamiliares(titularActualId); }
  else Toast.error(res.message);
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
