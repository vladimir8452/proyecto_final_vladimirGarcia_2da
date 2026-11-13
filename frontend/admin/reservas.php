<?php
$pagina = 'Reservas del Día';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../../backend/config/database.php';
$empleadosReserva = Database::getConnection()->query("SELECT id,nombre,apellidos,codigo_empleado FROM usuarios WHERE activo=1 AND tipo_cuenta IN ('PENSIONADO','NO_PENSIONADO') ORDER BY nombre,apellidos")->fetchAll();
?>


<div class="card mb-2">
 <div class="card-header"><h3>📲 Registrar solicitud recibida por WhatsApp</h3></div>
 <div class="card-body" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
  <div class="form-group" style="min-width:260px;flex:1"><label class="form-label">Empleado titular</label><select id="solEmpleado" class="form-select" onchange="cargarFamiliaresReserva()"><option value="">Seleccionar empleado...</option><?php foreach($empleadosReserva as $e): ?><option value="<?= (int)$e['id'] ?>"><?= htmlspecialchars($e['nombre'].' '.$e['apellidos'].' · '.$e['codigo_empleado']) ?></option><?php endforeach; ?></select></div>
  <div class="form-group" style="min-width:220px;flex:1"><label class="form-label">Beneficiario</label><select id="solFamiliar" class="form-select" onchange="actualizarResumenSolicitud()"><option value="0">Titular</option></select></div>
  <div class="form-group"><label class="form-label">Fecha del almuerzo</label><input type="date" id="solFecha" class="form-control" value="<?= date('Y-m-d') ?>" onchange="actualizarResumenSolicitud()"></div>
  <button class="btn btn-primary" onclick="registrarSolicitudWhatsApp()">Confirmar reserva esporádica</button>
  <div id="solResumen" class="alert alert-info" style="display:none;flex-basis:100%;margin:0;font-size:.88rem;"></div>
 </div>
 <div id="solMensaje" style="padding:0 1rem 1rem"></div>
</div>

<div class="card">
  <div class="card-header">
    <h3>📅 Reservas por Fecha</h3>
    <div style="display:flex;gap:.5rem;align-items:center;">
      <input type="date" id="fechaConsulta" class="form-control" style="width:180px;" value="<?= date('Y-m-d') ?>">
      <button class="btn btn-primary" onclick="cargarReservas()">Consultar</button>
      <button class="btn btn-secondary" onclick="exportarCSV()">⬇ CSV</button>
    </div>
  </div>
  <div class="card-body" style="padding:0;">
    <div id="resumenReservas" style="padding:1rem;background:#f8fafc;border-bottom:1px solid var(--border);display:flex;gap:2rem;font-size:.88rem;"></div>
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>#</th>
            <th>Titular</th>
            <th>Código</th>
            <th>Beneficiario</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Hora Reserva</th>
          </tr>
        </thead>
        <tbody id="tbodyReservas">
          <tr><td colspan="7" class="text-center text-muted" style="padding:2rem;">Selecciona una fecha para ver las reservas.</td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<script>
let reservasData = [];

async function cargarFamiliaresReserva(){
  const id=document.getElementById('solEmpleado').value;
  const sel=document.getElementById('solFamiliar');
  sel.innerHTML='<option value="0">Titular</option>';
  if(id){
    const r=await apiGet('/backend/api/familiares.php',{action:'listar',titular_id:id});
    (r.data||[]).filter(f=>f.estado==='ACTIVO').forEach(f=>{const o=document.createElement('option');o.value=f.id;o.textContent=f.nombre+' · '+(f.parentesco||'Familiar');sel.appendChild(o);});
    const inv=document.createElement('option');inv.value='-1';inv.textContent='Invitado (conocido o familiar no registrado)';sel.appendChild(inv);
  }
  actualizarResumenSolicitud();
}
function datosSolicitud(){
  const selE=document.getElementById('solEmpleado'), selF=document.getElementById('solFamiliar');
  const empleado=selE.value?selE.options[selE.selectedIndex].text:'';
  const fam=selF.value;
  const beneficiario=fam==='0'?'el propio titular':(fam==='-1'?'un invitado (conocido o familiar no registrado)':selF.options[selF.selectedIndex].text);
  return {empleado,beneficiario,fecha:document.getElementById('solFecha').value};
}
function actualizarResumenSolicitud(){
  const el=document.getElementById('solResumen'), d=datosSolicitud();
  if(!d.empleado){el.style.display='none';return;}
  el.style.display='block';
  el.innerHTML='Se reservará el almuerzo para: <strong>'+escHtml(d.beneficiario)+'</strong><br>Empleado titular (a quien se cobra): <strong>'+escHtml(d.empleado)+'</strong><br>Fecha: <strong>'+escHtml(d.fecha)+'</strong> · Costo: Bs <?= number_format(TARIFA_NO_PENSIONADO, 0) ?> (esporádico)';
}
async function registrarSolicitudWhatsApp(){const empleado=document.getElementById('solEmpleado').value;const fecha=document.getElementById('solFecha').value;const familiar=document.getElementById('solFamiliar').value;if(!empleado||!fecha){Toast.warning('Selecciona empleado y fecha.');return;}const d=datosSolicitud();if(!confirmar('¿Estás seguro de que quieres reservar el almuerzo del '+d.fecha+' para '+d.beneficiario+'?\n\nEmpleado titular (a quien se cobra): '+d.empleado+'\nCosto: Bs <?= number_format(TARIFA_NO_PENSIONADO, 0) ?> (esporádico)'))return;const r=await apiPost('/backend/api/reservas.php',{action:'registrar_esporadica',usuario_id:empleado,familiar_id:familiar,fecha});const el=document.getElementById('solMensaje');el.innerHTML='<div class="alert '+(r.success?'alert-success':'alert-danger')+'">'+r.message+'</div>';if(r.success){document.getElementById('fechaConsulta').value=fecha;cargarReservas();}}


async function cargarReservas() {
  const fecha = document.getElementById('fechaConsulta').value;
  if (!fecha) { Toast.warning('Selecciona una fecha.'); return; }

  const tbody = document.getElementById('tbodyReservas');
  tbody.innerHTML = '<tr><td colspan="7" class="text-center text-muted" style="padding:1.5rem;"><div class="spinner" style="margin:0 auto .4rem;"></div></td></tr>';

  const res = await apiGet('/backend/api/reservas.php', { action: 'listar_por_fecha', fecha });
  reservasData = res.data || [];

  if (!reservasData.length) {
    tbody.innerHTML = `<tr><td colspan="7" class="text-center text-muted" style="padding:2rem;">Sin reservas para el ${formatDateShort(fecha)}.</td></tr>`;
    document.getElementById('resumenReservas').innerHTML = '';
    return;
  }

  const activas   = reservasData.filter(r => ['RESERVADO','ENTREGADO','INASISTENCIA'].includes(r.estado)).length;
  const canceladas = reservasData.filter(r => r.estado === 'CANCELADO').length;
  document.getElementById('resumenReservas').innerHTML = `
    <span>📅 Fecha: <strong>${formatDate(fecha)}</strong></span>
    <span>✅ Activas: <strong style="color:var(--success);">${activas}</strong></span>
    <span>❌ Canceladas: <strong style="color:var(--danger);">${canceladas}</strong></span>
    <span>Total: <strong>${reservasData.length}</strong></span>
  `;

  tbody.innerHTML = reservasData.map((r, i) => `
    <tr>
      <td>${i+1}</td>
      <td><strong>${escHtml(r.nombre_titular)}</strong></td>
      <td><span class="badge badge-muted">${escHtml(r.codigo_empleado || '—')}</span></td>
      <td>${r.tipo_beneficiario === 'FAMILIAR' ? escHtml(r.nombre_familiar || 'Familiar') : escHtml(r.nombre_titular)}</td>
      <td>${r.tipo_beneficiario === 'FAMILIAR'
        ? '<span class="badge badge-info">Familiar</span>'
        : '<span class="badge badge-primary">Titular</span>'}</td>
      <td>${r.estado === 'RESERVADO' ? '<span class="badge badge-success">Reservado</span>' : r.estado === 'ENTREGADO' ? '<span class="badge badge-info">Entregado</span>' : r.estado === 'INASISTENCIA' ? '<span class="badge badge-warning">Inasistencia cobrada</span>' : '<span class="badge badge-danger">Cancelado</span>'}</td>
      <td style="font-size:.8rem;color:var(--muted);">${r.fecha_creacion ? r.fecha_creacion.split(' ')[1]?.slice(0,5) : '—'}</td>
    </tr>
  `).join('');
}

function exportarCSV() {
  if (!reservasData.length) { Toast.warning('No hay datos para exportar.'); return; }
  const fecha = document.getElementById('fechaConsulta').value;
  const headers = ['#','Titular','Codigo','Beneficiario','Tipo','Estado','Hora'];
  const rows = reservasData.map((r, i) => [
    i+1,
    r.nombre_titular,
    r.codigo_empleado || '',
    r.tipo_beneficiario === 'FAMILIAR' ? r.nombre_familiar || 'Familiar' : r.nombre_titular,
    r.tipo_beneficiario,
    r.estado,
    r.fecha_creacion ? r.fecha_creacion.split(' ')[1]?.slice(0,5) : '',
  ]);
  const csv = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `reservas_${fecha}.csv`;
  a.click();
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

// Cargar reservas de hoy al entrar
cargarReservas();
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
