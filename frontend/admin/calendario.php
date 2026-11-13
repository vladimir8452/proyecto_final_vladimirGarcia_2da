<?php
$pagina = 'Calendario Operativo / Feriados';
require_once __DIR__ . '/layout.php';
$anioActual = date('Y');
$mesActual  = (int) date('m');
?>

<div style="display:grid;grid-template-columns:1.3fr 1fr;gap:1.5rem;">

  <!-- Calendario del mes -->
  <div>
    <div class="card">
      <div class="card-header">
        <h3>🗓️ Días Operativos</h3>
        <div style="display:flex;gap:.5rem;align-items:center;">
          <select id="selAnio" class="form-select" style="width:90px;" onchange="cargarCalendario()">
            <?php for ($y = $anioActual-1; $y <= $anioActual+2; $y++): ?>
              <option value="<?= $y ?>" <?= $y == $anioActual ? 'selected' : '' ?>><?= $y ?></option>
            <?php endfor; ?>
          </select>
          <select id="selMes" class="form-select" style="width:120px;" onchange="cargarCalendario()">
            <?php $meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
            for ($m = 1; $m <= 12; $m++): ?>
              <option value="<?= $m ?>" <?= $m == $mesActual ? 'selected' : '' ?>><?= $meses[$m] ?></option>
            <?php endfor; ?>
          </select>
        </div>
      </div>
      <div class="card-body">
        <div id="calGrid" class="cal-grid">
          <p class="text-muted">Cargando...</p>
        </div>
        <div class="mt-2" style="display:flex;gap:1rem;font-size:.8rem;">
          <span style="display:flex;align-items:center;gap:.3rem;">
            <span style="width:14px;height:14px;background:#dcfce7;border-radius:3px;border:1px solid #15803d;display:inline-block;"></span> Operativo
          </span>
          <span style="display:flex;align-items:center;gap:.3rem;">
            <span style="width:14px;height:14px;background:#fff7ed;border-radius:3px;border:1px solid #d97706;display:inline-block;"></span> Feriado
          </span>
        </div>
      </div>
    </div>
  </div>

  <!-- Panel marcar feriado -->
  <div>
    <div class="card mb-2">
      <div class="card-header"><h3>Marcar Feriado</h3></div>
      <div class="card-body">
        <div id="alertFeriado"></div>
        <div class="form-group">
          <label class="form-label">Fecha *</label>
          <input type="date" id="feriadoFecha" class="form-control">
        </div>
        <div class="form-group">
          <label class="form-label">Descripción / Motivo</label>
          <input type="text" id="feriadoDesc" class="form-control" placeholder="Ej: Día del Trabajador">
        </div>
        <button class="btn btn-warning w-100" onclick="marcarFeriado()">Marcar como Feriado</button>
      </div>
    </div>

    <!-- Lista de feriados -->
    <div class="card">
      <div class="card-header">
        <h3>Feriados Registrados</h3>
        <button class="btn btn-secondary btn-sm" onclick="cargarFeriados()">↺</button>
      </div>
      <div class="card-body" style="padding:0;max-height:360px;overflow-y:auto;">
        <div id="listaFeriados">
          <p class="text-muted text-center" style="padding:1rem;">Cargando...</p>
        </div>
      </div>
    </div>
  </div>

</div>

<script>
async function cargarCalendario() {
  const anio = parseInt(document.getElementById('selAnio').value);
  const mes  = parseInt(document.getElementById('selMes').value);
  const res  = await apiGet('/backend/api/calendario.php', { action: 'mes', anio, mes });
  const dias = res.data || [];
  const grid = document.getElementById('calGrid');

  if (!dias.length) {
    grid.innerHTML = '<p class="text-muted">No hay días Lunes-Jueves en este mes.</p>';
    return;
  }

  grid.innerHTML = dias.map(d => `
    <div class="cal-day ${d.es_feriado ? 'feriado' : ''}" onclick="seleccionarDia('${d.fecha}','${escHtml(d.descripcion)}')">
      <div class="cal-day-name">${d.nombre_dia}</div>
      <div class="cal-date">${d.fecha.split('-')[2]}</div>
      <div class="cal-status">
        ${d.es_feriado
          ? `<span class="badge badge-warning" style="font-size:.65rem;">Feriado</span>`
          : `<span class="badge badge-success" style="font-size:.65rem;">Operativo</span>`}
      </div>
      ${d.descripcion ? `<div style="font-size:.65rem;color:#92400e;margin-top:.2rem;">${escHtml(d.descripcion)}</div>` : ''}
    </div>
  `).join('');
}

function seleccionarDia(fecha, desc) {
  document.getElementById('feriadoFecha').value = fecha;
  document.getElementById('feriadoDesc').value  = desc || '';
}

async function marcarFeriado() {
  const fecha = document.getElementById('feriadoFecha').value;
  const desc  = document.getElementById('feriadoDesc').value.trim();
  const alertEl = document.getElementById('alertFeriado');
  alertEl.innerHTML = '';

  if (!fecha) {
    alertEl.innerHTML = '<div class="alert alert-warning">Selecciona una fecha.</div>';
    return;
  }

  const res = await apiPost('/backend/api/calendario.php', { action: 'marcar_feriado', fecha, descripcion: desc });

  if (res.success) {
    Toast.success(res.message);
    cargarCalendario();
    cargarFeriados();
    document.getElementById('feriadoFecha').value = '';
    document.getElementById('feriadoDesc').value  = '';
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
  }
}

async function quitarFeriado(fecha) {
  if (!confirmar(`¿Quitar el feriado del ${fecha}?`)) return;
  const res = await apiPost('/backend/api/calendario.php', { action: 'quitar_feriado', fecha });
  if (res.success) { Toast.success(res.message); cargarCalendario(); cargarFeriados(); }
  else Toast.error(res.message);
}

async function cargarFeriados() {
  const res     = await apiGet('/backend/api/calendario.php', { action: 'listar_feriados' });
  const feriados = res.data || [];
  const lista   = document.getElementById('listaFeriados');

  if (!feriados.length) {
    lista.innerHTML = '<p class="text-muted text-center" style="padding:1rem;">Sin feriados registrados.</p>';
    return;
  }

  lista.innerHTML = feriados.map(f => `
    <div style="display:flex;align-items:center;justify-content:space-between;padding:.65rem 1rem;border-bottom:1px solid #eef2f7;">
      <div>
        <div style="font-weight:600;font-size:.88rem;">${formatDateShort(f.fecha)}</div>
        <div style="font-size:.78rem;color:var(--muted);">${escHtml(f.descripcion_feriado || 'Sin descripción')}</div>
      </div>
      <button class="btn btn-danger btn-sm" onclick="quitarFeriado('${f.fecha}')">✕</button>
    </div>
  `).join('');
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

cargarCalendario();
cargarFeriados();
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
