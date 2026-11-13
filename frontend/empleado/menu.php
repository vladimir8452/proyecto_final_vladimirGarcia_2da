<?php
$pagina = 'Menú Semanal';
require_once __DIR__ . '/layout.php';

$tipoCuenta = $_SESSION['tipo_cuenta'];
$tarifa     = $tipoCuenta === 'PENSIONADO' ? TARIFA_PENSIONADO : TARIFA_NO_PENSIONADO;
$hoy        = date('Y-m-d');
?>

<!-- Navegación de semana -->
<div class="card mb-2">
  <div class="card-body" style="display:flex;align-items:center;gap:1rem;flex-wrap:wrap;">
    <button class="btn btn-secondary" id="btnSemAnterior" onclick="cambiarSemana(-1)">← Sem. Anterior</button>
    <div style="flex:1;text-align:center;">
      <strong id="labelSemana">Cargando...</strong>
    </div>
    <button class="btn btn-secondary" id="btnSemSiguiente" onclick="cambiarSemana(1)">Sem. Siguiente →</button>
    <button class="btn btn-outline-primary" onclick="irSemanaActual()">Hoy</button>
  </div>
</div>

<!-- Grid de la semana (L–J) -->
<div id="weekGrid" class="week-grid">
  <!-- se rellena por JS -->
</div>

<!-- Mensajes de alerta bajo el grid -->
<div id="alertMenu" class="mt-2"></div>

<script>
/* ── estado ────────────────────────────────────────────────── */
let lunesActual  = getLunesActual();
let cargando     = false;          // evita llamadas solapadas
const TARIFA     = <?= $tarifa ?>;
const ES_PENSION = <?= $tipoCuenta === 'PENSIONADO' ? 'true' : 'false' ?>;

/* ── navegación ────────────────────────────────────────────── */
function cambiarSemana(delta) {
  if (cargando) return;
  const d = new Date(lunesActual + 'T00:00:00');
  d.setDate(d.getDate() + delta * 7);
  lunesActual = d.toISOString().split('T')[0];
  cargarSemana();
}

function irSemanaActual() {
  if (cargando) return;
  lunesActual = getLunesActual();
  cargarSemana();
}

/* ── helpers ────────────────────────────────────────────────── */
function mostrarError(msg) {
  const grid = document.getElementById('weekGrid');
  grid.innerHTML = `
    <div style="grid-column:1/-1;text-align:center;padding:3rem 1rem;">
      <div style="font-size:2.5rem;margin-bottom:.75rem;">⚠️</div>
      <p style="color:var(--danger);font-weight:600;">${escHtml(msg)}</p>
      <button class="btn btn-outline-primary mt-2" onclick="cargarSemana()">↺ Reintentar</button>
    </div>`;
}

function mostrarSpinner() {
  const grid = document.getElementById('weekGrid');
  grid.innerHTML = `
    <div style="grid-column:1/-1;display:flex;flex-direction:column;
                align-items:center;justify-content:center;padding:3rem 1rem;gap:.75rem;">
      <div class="spinner"></div>
      <span class="text-muted" style="font-size:.88rem;">Cargando menú...</span>
    </div>`;
}

function escHtml(str) {
  if (!str) return '';
  return String(str)
    .replace(/&/g,'&amp;').replace(/</g,'&lt;')
    .replace(/>/g,'&gt;').replace(/"/g,'&quot;');
}

/* ── helper: fecha local YYYY-MM-DD sin desfase UTC ────────────── */
function fechaLocal(dateObj) {
  const y = dateObj.getFullYear();
  const m = String(dateObj.getMonth() + 1).padStart(2, '0');
  const d = String(dateObj.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

/* ── carga principal ────────────────────────────────────────── */
async function cargarSemana() {
  if (cargando) return;
  cargando = true;

  document.getElementById('btnSemAnterior').disabled  = true;
  document.getElementById('btnSemSiguiente').disabled = true;
  document.getElementById('alertMenu').innerHTML       = '';

  mostrarSpinner();

  try {
    // ── calcular rango de la semana ─────────────────────────
    const lunes  = new Date(lunesActual + 'T00:00:00');
    const jueves = new Date(lunesActual + 'T00:00:00');
    jueves.setDate(lunes.getDate() + 3);
    const juevesStr = fechaLocal(jueves);

    document.getElementById('labelSemana').textContent =
      `${formatDateShort(lunesActual)} — ${formatDateShort(juevesStr)}`;

    // ── petición 1: menús de la semana ─────────────────────
    const resMenus = await apiGet('/backend/api/menus.php', {
      action:       'semana',
      fecha_inicio: lunesActual,
    });

    if (!resMenus.success) {
      console.warn('menus.php devolvió error:', resMenus);
      mostrarError(resMenus.message || 'No se pudieron cargar los menús.');
      return;
    }

    const menusArr = Array.isArray(resMenus.data) ? resMenus.data : [];
    const menuMap  = {};
    menusArr.forEach(m => { menuMap[m.fecha] = m; });

    // ── petición 2: feriados del mes ────────────────────────
    const resCal = await apiGet('/backend/api/calendario.php', {
      action: 'mes',
      anio:   lunes.getFullYear(),
      mes:    lunes.getMonth() + 1,
    });

    const calMap = {};
    if (resCal.success && Array.isArray(resCal.data)) {
      resCal.data.forEach(c => { calMap[c.fecha] = c; });
    } else {
      console.warn('calendario.php respuesta inesperada (sin feriados):', resCal);
    }

    // ── generar los 4 días L–J ──────────────────────────────
    const dias = [];
    for (let i = 0; i < 4; i++) {
      const d = new Date(lunesActual + 'T00:00:00');
      d.setDate(d.getDate() + i);
      dias.push(fechaLocal(d));       // ← fecha local, no UTC
    }

    const todayStr   = hoy();
    const diasNombre = { 1:'Lunes', 2:'Martes', 3:'Miércoles', 4:'Jueves' };

    const html = dias.map(fecha => {
      const menu      = menuMap[fecha] || null;
      const cal       = calMap[fecha]  || null;
      const esFeriado = cal && Boolean(Number(cal.es_feriado));
      const esHoy     = fecha === todayStr;

      // Calcular nombre del día en zona local
      const dtDia     = new Date(fecha + 'T00:00:00');
      const diaSemana = dtDia.getDay() === 0 ? 7 : dtDia.getDay(); // dom=7, lun=1…
      const nombreDia = diasNombre[diaSemana] || '';

      let contenido;
      if (esFeriado) {
        contenido = `
          <div class="day-menu" style="color:#92400e;">
            🚫 Feriado
            ${cal.descripcion ? `<br><small>${escHtml(cal.descripcion)}</small>` : ''}
          </div>`;
      } else if (menu) {
        contenido = `
          <div class="day-menu">${escHtml(menu.descripcion_menu)}</div>
          <div style="margin-top:.6rem;display:flex;flex-direction:column;gap:.3rem;">
            <span class="badge badge-info" style="font-size:.7rem;">${ES_PENSION ? 'Pensión Bs <?= number_format(TARIFA_PENSIONADO, 0) ?> · Esporádico Bs <?= number_format(TARIFA_NO_PENSIONADO, 0) ?>' : 'Bs ' + TARIFA + '/almuerzo'}</span>
            <button class="btn btn-primary btn-sm" style="margin-top:.3rem;"
                    onclick="reservar('${fecha}', false)">
              🍽️ Reservar
            </button>
            ${ES_PENSION ? `
            <button class="btn btn-secondary btn-sm"
                    onclick="abrirReservarFamiliar('${fecha}')">
              👨‍👩‍👧 Reservar Familiar
            </button>` : ''}
          </div>`;
      } else {
        contenido = `
          <div class="day-menu text-muted" style="font-size:.82rem;padding:.4rem 0;">
            📭 Sin menú publicado
          </div>`;
      }

      return `
        <div class="week-day-card ${esHoy ? 'today' : ''} ${esFeriado ? 'feriado' : ''}">
          <div class="day-name">${nombreDia}${esHoy
            ? ' <span style="color:var(--accent);font-size:.75rem;">• Hoy</span>' : ''}</div>
          <div class="day-date">${fecha.split('-')[2]}</div>
          ${contenido}
        </div>`;
    }).join('');

    document.getElementById('weekGrid').innerHTML = html
      || `<div style="grid-column:1/-1;text-align:center;padding:3rem;color:var(--muted);">
            📭 No hay menús publicados para esta semana.
          </div>`;

  } catch (err) {
    console.error('cargarSemana() excepción inesperada:', err);
    mostrarError('Ocurrió un error inesperado. Revisa la consola del navegador (F12).');
  } finally {
    cargando = false;
    document.getElementById('btnSemAnterior').disabled  = false;
    document.getElementById('btnSemSiguiente').disabled = false;
  }
}

/* ── reservar titular ───────────────────────────────────────── */
async function reservar(fecha, esFamiliar, familiarId = null) {
  const alertEl = document.getElementById('alertMenu');
  alertEl.innerHTML = '';

  const data = esFamiliar && familiarId
    ? { action: 'reservar_familiar', fecha, familiar_id: familiarId }
    : { action: 'reservar_titular',  fecha };

  const res = await apiPost('/backend/api/reservas.php', data);

  if (res.success) {
    Toast.success(res.message);
    cargarSemana();
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">⚠ ${escHtml(res.message)}</div>`;
  }
}

/* ── reservar familiar (solo PENSIONADO) ────────────────────── */
<?php if ($tipoCuenta === 'PENSIONADO'): ?>
let fechaFamiliarPendiente = null;

async function abrirReservarFamiliar(fecha) {
  fechaFamiliarPendiente = fecha;

  const res  = await apiGet('/backend/api/familiares.php', { action: 'listar' });
  const fams = Array.isArray(res.data) ? res.data.filter(f => f.estado === 'ACTIVO') : [];

  // Eliminar modal previo si existe
  document.getElementById('modalSelFam')?.remove();

  const opcs = fams
    .map(f => `<option value="${f.id}">${escHtml(f.nombre)}</option>`)
    .join('') + '<option value="0">Invitado (conocido o familiar extra) — Bs <?= number_format(TARIFA_NO_PENSIONADO, 0) ?></option>';

  const wrap = document.createElement('div');
  wrap.innerHTML = `
    <div class="modal-overlay open" id="modalSelFam">
      <div class="modal" style="max-width:360px;">
        <div class="modal-header">
          <h4>Reservar para Familiar o Invitado</h4>
          <button class="btn-close" onclick="document.getElementById('modalSelFam').remove()">×</button>
        </div>
        <div class="modal-body">
          <p style="font-size:.85rem;color:var(--muted);margin-bottom:.75rem;">
            Fecha: <strong>${formatDateShort(fecha)}</strong>
          </p>
          <div class="form-group">
            <label class="form-label">Selecciona quién almorzará</label>
            <select id="selFamiliarReserva" class="form-select">${opcs}</select>
          </div>
        </div>
        <div class="modal-footer">
          <button class="btn btn-secondary"
                  onclick="document.getElementById('modalSelFam').remove()">Cancelar</button>
          <button class="btn btn-primary" onclick="confirmarReservaFamiliar()">Confirmar</button>
        </div>
      </div>
    </div>`;
  document.body.appendChild(wrap);
}

async function confirmarReservaFamiliar() {
  const familiarId = document.getElementById('selFamiliarReserva')?.value;
  document.getElementById('modalSelFam')?.remove();
  if (familiarId && fechaFamiliarPendiente) {
    await reservar(fechaFamiliarPendiente, true, familiarId);
    fechaFamiliarPendiente = null;
  }
}
<?php endif; ?>

/* ── arranque ───────────────────────────────────────────────── */
cargarSemana();
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
