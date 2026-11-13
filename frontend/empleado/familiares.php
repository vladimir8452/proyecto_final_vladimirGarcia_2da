<?php
$pagina = 'Mis Familiares';
require_once __DIR__ . '/layout.php';

// Solo pensionados
if ($_SESSION['tipo_cuenta'] !== 'PENSIONADO') {
    header('Location: ' . rtrim(BASE_URL, '/') . '/frontend/empleado/dashboard.php');
    exit;
}
$usuarioId = (int) $_SESSION['usuario_id'];
$cfg=Database::getConnection()->query('SELECT clave,valor FROM configuracion_sistema')->fetchAll(PDO::FETCH_KEY_PAIR);
$tarifaFamilia=(float)($cfg['tarifa_pensionado']??TARIFA_PENSIONADO); $minimoFamilia=(int)($cfg['minimo_pension']??MINIMO_ALMUERZOS_MES);
?>

<!-- Aviso informativo -->
<div class="alert alert-info mb-2">
  <strong>ℹ Información:</strong> Puedes registrar padres, madre, cónyuge e hijos. Los consumos se cargarán a tu cuenta titular.
</div>

<!-- Tarjeta de familiares -->
<div class="card">
  <div class="card-header">
    <h3>👨‍👩‍👧 Mis Familiares Vinculados</h3>
    <div style="display:flex;gap:.5rem"><button class="btn btn-secondary btn-sm" onclick="cargarFamiliares()">↺ Actualizar</button><button class="btn btn-primary btn-sm" onclick="openModal('modalNuevoFamiliar')">+ Registrar familiar</button></div>
  </div>
  <div class="card-body" style="padding:0;">
    <div id="listaFamiliares">
      <p class="text-muted text-center" style="padding:2rem;">
        <span class="spinner" style="display:inline-block;margin-right:.5rem;"></span> Cargando...
      </p>
    </div>
  </div>
</div>


<div class="modal-overlay" id="modalNuevoFamiliar"><div class="modal"><div class="modal-header"><h4>Registrar familiar directo</h4><button class="btn-close" onclick="closeModal('modalNuevoFamiliar')">×</button></div><div class="modal-body"><div id="altaFamiliarMsg"></div><div class="form-group"><label class="form-label">Nombre completo *</label><input id="nuevoFamNombre" class="form-control"></div><div class="form-group"><label class="form-label">CI *</label><input id="nuevoFamCi" class="form-control"></div><div class="form-group"><label class="form-label">Parentesco *</label><select id="nuevoFamParentesco" class="form-select"><option value="">Seleccionar...</option><option value="PADRE">Padre</option><option value="MADRE">Madre</option><option value="CONYUGE">Cónyuge</option><option value="HIJO">Hijo/a</option></select></div></div><div class="modal-footer"><button class="btn btn-secondary" onclick="closeModal('modalNuevoFamiliar')">Cancelar</button><button class="btn btn-primary" onclick="registrarFamiliarPropio()">Guardar</button></div></div></div>

<script>
async function cargarFamiliares() {
  const lista = document.getElementById('listaFamiliares');
  lista.innerHTML = '<p class="text-muted text-center" style="padding:2rem;">Cargando...</p>';

  const res  = await apiGet('/backend/api/familiares.php', { action: 'listar' });
  const fams = res.data || [];

  if (!fams.length) {
    lista.innerHTML = `
      <div class="empty-state">
        <div class="empty-icon">👥</div>
        <h4>Sin familiares vinculados</h4>
        <p>Usa el botón «Registrar familiar» para vincular a tus familiares directos.</p>
      </div>`;
    return;
  }

  const activos   = fams.filter(f => f.estado === 'ACTIVO').length;
  const tarifa    = <?= $tarifaFamilia ?>;
  const minimo    = <?= $minimoFamilia ?>;

  lista.innerHTML = `
    <div style="padding:.75rem 1.25rem;background:#f8fafc;border-bottom:1px solid var(--border);
                font-size:.85rem;display:flex;gap:2rem;flex-wrap:wrap;">
      <span>Familiares activos: <strong>${activos}</strong></span>
      <span>Tarifa: <strong>Bs ${tarifa}/almuerzo</strong></span>
      <span>Mínimo mensual por familiar: <strong>${minimo} almuerzos</strong></span>
    </div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Nombre</th><th>Parentesco</th><th>CI</th>
            <th>Estado</th>
            <th>Vinculado desde</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody>
          ${fams.map((f, i) => `
            <tr>
              <td>${i + 1}</td>
              <td><strong>${escHtml(f.nombre)}</strong></td><td>${escHtml(f.parentesco || '—')}</td><td>${escHtml(f.ci || '—')}</td>
              <td>${f.estado === 'ACTIVO' ? '<span class="badge badge-success">Activo</span>' : '<span class="badge badge-danger">Inactivo</span>'}</td>
              <td style="font-size:.8rem;color:var(--muted);">
                ${formatDateShort(f.created_at ? f.created_at.split(' ')[0] : '')}
              </td>
              <td>${f.estado === 'ACTIVO' ? '<span class="text-muted" style="font-size:.8rem;">Solicitar por WhatsApp al administrador</span>' : '<span class="text-muted" style="font-size:.8rem;">Inactivo</span>'}</td>
            </tr>
          `).join('')}
        </tbody>
      </table>
    </div>
  `;
}

async function registrarFamiliarPropio(){const nombre=document.getElementById('nuevoFamNombre').value.trim(),ci=document.getElementById('nuevoFamCi').value.trim(),parentesco=document.getElementById('nuevoFamParentesco').value;const box=document.getElementById('altaFamiliarMsg');if(!nombre||!ci||!parentesco){box.innerHTML='<div class="alert alert-warning">Completa todos los campos.</div>';return;}const r=await apiPost('/backend/api/familiares.php',{action:'registrar',nombre,ci,parentesco});box.innerHTML='<div class="alert '+(r.success?'alert-success':'alert-danger')+'">'+r.message+'</div>';if(r.success){Toast.success(r.message);closeModal('modalNuevoFamiliar');document.getElementById('nuevoFamNombre').value='';document.getElementById('nuevoFamCi').value='';document.getElementById('nuevoFamParentesco').value='';cargarFamiliares();}}

function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g, '&amp;')
    .replace(/</g, '&lt;')
    .replace(/>/g, '&gt;')
    .replace(/"/g, '&quot;');
}

cargarFamiliares();
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
