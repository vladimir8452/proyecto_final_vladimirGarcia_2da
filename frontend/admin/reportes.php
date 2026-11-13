<?php
$pagina = 'Reporte Mensual — Recursos Humanos';
require_once __DIR__ . '/layout.php';
$anioActual = (int) date('Y');
$mesActual  = (int) date('m');
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio','Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];
?>

<!-- Controles -->
<div class="card mb-2">
  <div class="card-header">
    <h3>📈 Reporte Consolidado Mensual</h3>
  </div>
  <div class="card-body">
    <div id="alertReporte"></div>
    <div style="display:flex;gap:1rem;align-items:flex-end;flex-wrap:wrap;">
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Mes</label>
        <select id="selMes" class="form-select" style="width:150px;">
          <?php for ($m = 1; $m <= 12; $m++): ?>
            <option value="<?= $m ?>" <?= $m == $mesActual ? 'selected' : '' ?>><?= $meses[$m] ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <div class="form-group" style="margin-bottom:0;">
        <label class="form-label">Año</label>
        <select id="selAnio" class="form-select" style="width:100px;">
          <?php for ($y = $anioActual-2; $y <= $anioActual; $y++): ?>
            <option value="<?= $y ?>" <?= $y == $anioActual ? 'selected' : '' ?>><?= $y ?></option>
          <?php endfor; ?>
        </select>
      </div>
      <button class="btn btn-primary" id="btnGenerar" onclick="generarReporte()">
        ⚙ Calcular y Generar
      </button>
      <button class="btn btn-secondary" onclick="verReporte()">
        👁 Ver Guardado
      </button>
      <button class="btn btn-success" onclick="exportarReporte()">
        ⬇ Exportar CSV
      </button>
      <button class="btn btn-outline-primary" onclick="window.print()">
        🖨 Imprimir
      </button>
    </div>
    <p class="text-muted mt-1" style="font-size:.8rem;">
      ℹ "Calcular y Generar" recalcula y sobreescribe el reporte del mes seleccionado.
    </p>
  </div>
</div>

<!-- Tabla del reporte -->
<div class="card" id="cardReporte" style="display:none;">
  <div class="card-body">
    <div class="reporte-header">
      <h3>Restaurante Institucional "Rumbo al Cielo" — Unión Boliviana</h3>
      <p id="reporteTitulo">Reporte de Descuentos por Planilla</p>
    </div>

    <!-- Leyenda -->
    <div style="display:flex;gap:1.5rem;flex-wrap:wrap;margin-bottom:1rem;font-size:.82rem;">
      <span><strong>Pensionado/Familiar:</strong> Bs <?= TARIFA_PENSIONADO ?>/almuerzo · Mínimo <?= MINIMO_ALMUERZOS_MES ?> almuerzos/mes</span>
      <span><strong>No Pensionado:</strong> Bs <?= TARIFA_NO_PENSIONADO ?>/almuerzo · Sin mínimo</span>
    </div>

    <div class="table-responsive">
      <table class="table" id="tablaReporte">
        <thead>
          <tr>
            <th>Cód. Empleado</th>
            <th>Nombre</th>
            <th>Almuerzos Titular</th>
            <th>Monto Titular (Bs)</th>
            <th>Almuerzos Familiares</th>
            <th>Monto Familiares (Bs)</th>
            <th>Penalización (Bs)</th>
            <th class="text-right">TOTAL A DESCONTAR (Bs)</th>
          </tr>
        </thead>
        <tbody id="tbodyReporte">
        </tbody>
        <tfoot>
          <tr class="total-row" id="footerReporte"></tr>
        </tfoot>
      </table>
    </div>
    <p class="text-muted mt-2" style="font-size:.78rem;">
      Generado el: <span id="reporteFecha">—</span>
    </p>
  </div>
</div>

<script>
let reporteData = [];

async function generarReporte() {
  const anio = parseInt(document.getElementById('selAnio').value);
  const mes  = parseInt(document.getElementById('selMes').value);
  const btn  = document.getElementById('btnGenerar');
  const alertEl = document.getElementById('alertReporte');
  alertEl.innerHTML = '';

  if (!confirmar(`¿Generar el reporte de ${MESES[mes]} ${anio}? Esto sobreescribirá el reporte existente.`)) return;

  btnLoading(btn, true);
  const res = await apiPost('/backend/api/reportes.php', { action: 'generar', anio, mes });

  if (res.success) {
    Toast.success(`Reporte generado: ${res.total_empleados} empleado(s).`);
    reporteData = res.filas || [];
    mostrarReporte(anio, mes, reporteData);
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">${res.message}</div>`;
  }
  btnLoading(btn, false);
}

async function verReporte() {
  const anio = parseInt(document.getElementById('selAnio').value);
  const mes  = parseInt(document.getElementById('selMes').value);
  const res  = await apiGet('/backend/api/reportes.php', { action: 'obtener', anio, mes });

  if (!res.data || !res.data.length) {
    Toast.warning('No hay reporte guardado para este período. Usa "Calcular y Generar" primero.');
    return;
  }
  reporteData = res.data;
  mostrarReporte(anio, mes, reporteData);
}

function mostrarReporte(anio, mes, datos) {
  document.getElementById('cardReporte').style.display = '';
  document.getElementById('reporteTitulo').textContent =
    `Reporte de Descuentos por Planilla — ${MESES[mes]} ${anio}`;
  document.getElementById('reporteFecha').textContent = new Date().toLocaleString('es-BO');

  const tbody = document.getElementById('tbodyReporte');
  const footer = document.getElementById('footerReporte');

  let totalAlmT = 0, totalMontoT = 0, totalAlmF = 0, totalMontoF = 0, totalPen = 0, totalFinal = 0;

  tbody.innerHTML = datos.map(r => {
    const detF   = parseDetalle(r.detalle_familiares);
    const montoF = detF.reduce((s, f) => s + parseFloat(f.monto || 0), 0);
    const pen    = parseFloat(r.penalizacion_familiares || 0);
    const total  = parseFloat(r.total_descuento || 0);

    totalAlmT  += parseInt(r.almuerzos_titular || 0);
    totalMontoT += parseFloat(r.monto_titular || 0);
    totalAlmF  += parseInt(r.almuerzos_familiares || 0);
    totalMontoF += montoF;
    totalPen   += pen;
    totalFinal += total;

    return `
      <tr>
        <td><span class="badge badge-muted">${escHtml(r.codigo_empleado)}</span></td>
        <td>
          <strong>${escHtml(r.nombre_empleado)}</strong>
          ${detF.length ? `<div style="font-size:.75rem;color:var(--muted);margin-top:.2rem;">
            Fam: ${detF.map(f => escHtml(f.nombre) + ' ('+f.almuerzos+'✓)').join(', ')}
          </div>` : ''}
        </td>
        <td class="text-center">${r.almuerzos_titular}</td>
        <td class="text-center">${formatBs(r.monto_titular)}</td>
        <td class="text-center">${r.almuerzos_familiares || 0}</td>
        <td class="text-center">${formatBs(montoF)}</td>
        <td class="text-center ${pen > 0 ? 'text-danger fw-bold' : ''}">${formatBs(pen)}</td>
        <td class="text-right fw-bold" style="font-size:1rem;">${formatBs(total)}</td>
      </tr>
    `;
  }).join('');

  footer.innerHTML = `
    <th colspan="2">TOTALES</th>
    <th class="text-center">${totalAlmT}</th>
    <th class="text-center">${formatBs(totalMontoT)}</th>
    <th class="text-center">${totalAlmF}</th>
    <th class="text-center">${formatBs(totalMontoF)}</th>
    <th class="text-center">${formatBs(totalPen)}</th>
    <th class="text-right" style="font-size:1.05rem;color:var(--primary);">${formatBs(totalFinal)}</th>
  `;

  document.getElementById('cardReporte').scrollIntoView({ behavior: 'smooth' });
}

function parseDetalle(raw) {
  if (!raw) return [];
  try {
    const parsed = typeof raw === 'string' ? JSON.parse(raw) : raw;
    return Array.isArray(parsed) ? parsed : [];
  } catch { return []; }
}

function exportarReporte() {
  if (!reporteData.length) { Toast.warning('Genera el reporte primero.'); return; }
  const anio = document.getElementById('selAnio').value;
  const mes  = document.getElementById('selMes').value;
  const headers = ['Codigo','Nombre','Alm.Titular','Monto Titular','Alm.Familiares','Monto Familiares','Penalizacion','Total Descuento'];
  const rows = reporteData.map(r => {
    const detF   = parseDetalle(r.detalle_familiares);
    const montoF = detF.reduce((s, f) => s + parseFloat(f.monto || 0), 0);
    return [
      r.codigo_empleado,
      r.nombre_empleado,
      r.almuerzos_titular,
      parseFloat(r.monto_titular).toFixed(2),
      r.almuerzos_familiares || 0,
      montoF.toFixed(2),
      parseFloat(r.penalizacion_familiares || 0).toFixed(2),
      parseFloat(r.total_descuento).toFixed(2),
    ];
  });
  const csv = [headers, ...rows].map(r => r.map(v => `"${v}"`).join(',')).join('\n');
  const blob = new Blob(['\uFEFF' + csv], { type: 'text/csv;charset=utf-8;' });
  const a = document.createElement('a');
  a.href = URL.createObjectURL(blob);
  a.download = `reporte_planilla_${mes}_${anio}.csv`;
  a.click();
}

function escHtml(str) {
  if (!str) return '';
  return String(str).replace(/&/g,'&amp;').replace(/</g,'&lt;').replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
