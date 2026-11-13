<?php
$pagina = 'Mis Reservas';
require_once __DIR__ . '/layout.php';

$tipoCuenta  = $_SESSION['tipo_cuenta'];
$esPensionado = $tipoCuenta === 'PENSIONADO';
$cfg=Database::getConnection()->query('SELECT clave,valor FROM configuracion_sistema')->fetchAll(PDO::FETCH_KEY_PAIR);
$tarifa       = $esPensionado ? (float)($cfg['tarifa_pensionado']??TARIFA_PENSIONADO) : (float)($cfg['tarifa_esporadico']??TARIFA_NO_PENSIONADO);
$minimoActual = (int)($cfg['minimo_pension']??MINIMO_ALMUERZOS_MES);
$anio         = date('Y');
$mes          = (int) date('m');
$meses        = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>

<div class="alert alert-info mb-2"><strong>Reservas esporádicas:</strong> solicita tu almuerzo por WhatsApp al administrador antes de las 09:00 AM. El administrador registrará la confirmación en el sistema. Las cancelaciones de pensionados se realizan desde esta lista antes del corte.</div>

<!-- Filtros -->
<div class="card mb-2">
  <div class="card-body" style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Desde</label>
      <input type="date" id="filtroDesde" class="form-control" style="width:160px;" value="<?= date('Y-m-01') ?>">
    </div>
    <div class="form-group" style="margin-bottom:0;">
      <label class="form-label">Hasta</label>
      <input type="date" id="filtroHasta" class="form-control" style="width:160px;" value="<?= date('Y-m-t') ?>">
    </div>
    <button class="btn btn-primary" onclick="cargarReservas()">Filtrar</button>
    <button class="btn btn-secondary" onclick="exportarReservas()">⬇ CSV</button>
  </div>
</div>

<!-- Resumen del mes -->
<?php if ($esPensionado): ?>
<div class="card mb-2" id="cardResumen">
  <div class="card-body" style="display:flex;gap:2rem;flex-wrap:wrap;align-items:center;">
    <div>
      <div style="font-size:.8rem;color:var(--muted);">Almuerzos del mes (titular)</div>
      <div id="resAlmT" style="font-size:1.8rem;font-weight:700;color:var(--primary);">—</div>
    </div>
    <div>
      <div style="font-size:.8rem;color:var(--muted);">Mínimo obligatorio</div>
      <div style="font-size:1.8rem;font-weight:700;color:var(--muted);"><?= $minimoActual ?></div>
    </div>
    <div>
      <div style="font-size:.8rem;color:var(--muted);">Costo estimado (titular)</div>
      <div id="resCostoT" style="font-size:1.8rem;font-weight:700;color:var(--success);">—</div>
    </div>
    <div id="resAlerta"></div>
  </div>
</div>
<?php endif; ?>

<!-- Tabla -->
<div class="card">
  <div class="card-header">
    <h3>📅 Historial de Reservas</h3>
    <span id="totalLabel" class="badge badge-muted">—</span>
  </div>
  <div class="card-body" style="padding:0;">
    <div class="table-responsive">
      <table class="table">
        <thead>
          <tr>
            <th>Fecha</th>
            <th>Día</th>
            <th>Para</th>
            <th>Tipo</th>
            <th>Estado</th>
            <th>Acción</th>
          </tr>
        </thead>
        <tbody id="tbodyReservas">
          <tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">
            <div class="spinner" style="margin:0 auto .4rem;"></div>
          </td></tr>
        </tbody>
      </table>
    </div>
  </div>
</div>

<div id="alertReservas" class="mt-2"></div>

<script>
let reservasData = [];
const tarifa = <?= $tarifa ?>;
const minimo = <?= $minimoActual ?>;
const esPensionado = <?= $esPensionado ? 'true' : 'false' ?>;

async function cargarReservas() {
  const desde = document.getElementById('filtroDesde').value;
  const hasta = document.getElementById('filtroHasta').value;
  const tbody = document.getElementById('tbodyReservas');
  tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:1.5rem;"><div class="spinner" style="margin:0 auto;"></div></td></tr>';

  const params = { action: 'listar' };
  if (desde) params.fecha_inicio = desde;
  if (hasta) params.fecha_fin = hasta;

  const res = await apiGet('/backend/api/reservas.php', params);
  reservasData = res.data || [];

  document.getElementById('totalLabel').textContent = `${reservasData.length} reserva(s)`;
  actualizarResumen();
  renderTabla();
}

function actualizarResumen() {
  if (!esPensionado) return;
  const activas = reservasData.filter(r => ['RESERVADO','ENTREGADO','INASISTENCIA'].includes(r.estado) && r.tipo_beneficiario === 'TITULAR');
  const n = activas.length;
  const monto = Math.max(n, minimo) * tarifa; // mínimo siempre se cobra

  document.getElementById('resAlmT').textContent  = n;
  document.getElementById('resCostoT').textContent = formatBs(n * tarifa);

  const alerta = document.getElementById('resAlerta');
  if (n < minimo) {
    alerta.innerHTML = `<div class="alert alert-warning" style="margin:0;">
      Faltan <strong>${minimo - n}</strong> almuerzo(s) para el mínimo.
      Se cobrarán <strong>${formatBs(minimo * tarifa)}</strong> este mes.
    </div>`;
  } else {
    alerta.innerHTML = `<div class="alert alert-success" style="margin:0;">
      ✅ Cumples el mínimo de ${minimo} almuerzos.
    </div>`;
  }
}

function renderTabla() {
  const tbody = document.getElementById('tbodyReservas');
  const hoyStr = hoy();
  const horaLimite = '09:00';

  if (!reservasData.length) {
    tbody.innerHTML = '<tr><td colspan="6" class="text-center text-muted" style="padding:2rem;">Sin reservas en el período seleccionado.</td></tr>';
    return;
  }

  tbody.innerHTML = reservasData.map(r => {
    const esHoy = r.fecha_reserva === hoyStr;
    const hora  = horaActual();
    const puedeCanc = r.estado === 'RESERVADO'
      && r.fecha_reserva >= hoyStr
      && !(esHoy && hora >= horaLimite);

    const d = new Date(r.fecha_reserva + 'T00:00:00');
    const diasMap = {1:'Lun',2:'Mar',3:'Mié',4:'Jue',5:'Vie',6:'Sáb',0:'Dom'};

    return `
      <tr>
        <td><strong>${formatDateShort(r.fecha_reserva)}</strong></td>
        <td>${diasMap[d.getDay()] || ''}</td>
        <td>${r.tipo_beneficiario === 'FAMILIAR'
          ? '<span class="badge badge-info">👨‍👩‍👧 ' + escHtml(r.nombre_familiar || 'Familiar') + '</span>'
          : '<span class="badge badge-primary">👤 Titular</span>'}</td>
        <td>${r.tipo_beneficiario === 'TITULAR' ? 'Titular' : 'Familiar'}</td>
        <td>${r.estado === 'RESERVADO' ? '<span class="badge badge-success">Reservado</span>' : r.estado === 'ENTREGADO' ? '<span class="badge badge-info">Entregado</span>' : r.estado === 'INASISTENCIA' ? '<span class="badge badge-warning">Cobrada por inasistencia</span>' : '<span class="badge badge-danger">Cancelado</span>'}</td>
        <td>
          ${puedeCanc
            ? `<button class="btn btn-danger btn-sm" onclick="cancelar(${r.id},'${r.fecha_reserva}')">Cancelar</button>`
            : (r.estado === 'CANCELADO'
              ? '<span class="text-muted" style="font-size:.8rem;">Cancelada</span>'
              : '<span class="text-muted" style="font-size:.8rem;">Fuera de plazo</span>')}
        </td>
      </tr>
    `;
  }).join('');
}

async function cancelar(reservaId, fecha) {
  const hoyStr = hoy();
  const hora   = horaActual();
  if (fecha === hoyStr && hora >= '09:00') {
    Toast.error('No se puede cancelar reservas del día de hoy después de las 09:00 a.m.');
    return;
  }
  if (!confirmar('¿Cancelar esta reserva?')) return;

  const alertEl = document.getElementById('alertReservas');
  alertEl.innerHTML = '';

  const res = await apiPost('/backend/api/reservas.php', { action: 'cancelar', reserva_id: reservaId });
  if (res.success) {
    Toast.success(res.message);
    cargarReservas();
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">⚠ ${res.message}</div>`;
  }
}

function exportarReservas() {
  if (!reservasData.length) { Toast.warning('Sin datos para exportar.'); return; }
  const headers = ['Fecha','Dia','Para','Tipo','Estado'];
  const rows = reservasData.map(r => {
    const d = new Date(r.fecha_reserva + 'T00:00:00');
    const dias = {1:'Lun',2:'Mar',3:'Mié',4:'Jue',5:'Vie',6:'Sáb',0:'Dom'};
    return [
      formatDateShort(r.fecha_reserva),
      dias[d.getDay()] || '',
      r.tipo_beneficiario === 'FAMILIAR' ? r.nombre_familiar || 'Familiar' : 'Titular',
      r.tipo_beneficiario,
      r.estado,
    ];
  });
  const csv = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a'); a.href = URL.createObjectURL(blob);
  a.download = `mis_reservas.csv`; a.click();
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

cargarReservas();
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
