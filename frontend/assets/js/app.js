/**
 * app.js — Utilidades globales del sistema
 * Restaurante "Rumbo al Cielo"
 */

// ── Toast notifications ─────────────────────────────────────────
const Toast = (() => {
  let container = null;

  function getContainer() {
    if (!container) {
      container = document.createElement('div');
      container.className = 'toast-container';
      document.body.appendChild(container);
    }
    return container;
  }

  function show(message, type = 'success', duration = 3500) {
    const icons = { success: '✓', error: '✕', warning: '⚠' };
    const el = document.createElement('div');
    el.className = `toast ${type}`;
    el.innerHTML = `<span>${icons[type] || '●'}</span><span>${message}</span>`;
    getContainer().appendChild(el);

    setTimeout(() => {
      el.style.opacity = '0';
      el.style.transform = 'translateX(100%)';
      el.style.transition = 'opacity .3s, transform .3s';
      setTimeout(() => el.remove(), 350);
    }, duration);
  }

  return { show, success: (m) => show(m,'success'), error: (m) => show(m,'error'), warning: (m) => show(m,'warning') };
})();

// ── API wrapper (fetch + JSON) ──────────────────────────────────
async function api(url, options = {}) {
  try {
    const res = await fetch(url, {
      headers: { 'X-Requested-With': 'XMLHttpRequest', ...options.headers },
      ...options,
    });

    // Leer el texto crudo primero para poder depurar respuestas no-JSON
    const text = await res.text();

    if (!res.ok) {
      console.error(`API HTTP ${res.status} → ${url}`, text);
      return { success: false, message: `Error del servidor (HTTP ${res.status}).`, _raw: text };
    }

    try {
      return JSON.parse(text);
    } catch (parseErr) {
      console.error('API respuesta no es JSON válido:', url, text);
      return { success: false, message: 'Respuesta inválida del servidor.', _raw: text };
    }

  } catch (networkErr) {
    console.error('API error de red:', url, networkErr);
    return { success: false, message: 'Error de conexión. Verifica tu red e intenta nuevamente.' };
  }
}

async function apiPost(url, data) {
  const body = new FormData();
  Object.entries(data).forEach(([k, v]) => body.append(k, v ?? ''));
  return api(_url(url), { method: 'POST', body });
}

async function apiGet(url, params = {}) {
  const qs = new URLSearchParams(params).toString();
  const full = _url(url);
  return api(qs ? `${full}?${qs}` : full);
}

// Prefija BASE_URL si la URL empieza con '/' (ruta relativa al proyecto)
function _url(path) {
  if (typeof BASE_URL !== 'undefined' && path.startsWith('/')) {
    return BASE_URL.replace(/\/$/, '') + path;
  }
  return path;
}

// ── Modal helpers ───────────────────────────────────────────────
function openModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.add('open');
}

function closeModal(id) {
  const el = document.getElementById(id);
  if (el) el.classList.remove('open');
}

// Cerrar modal al hacer clic en el overlay
document.addEventListener('click', (e) => {
  if (e.target.classList.contains('modal-overlay')) {
    e.target.classList.remove('open');
  }
});

// ── Sidebar toggle (mobile) ─────────────────────────────────────
document.addEventListener('DOMContentLoaded', () => {
  const toggle  = document.getElementById('sidebarToggle');
  const sidebar = document.getElementById('sidebar');
  const overlay = document.getElementById('sidebarOverlay');

  if (toggle && sidebar) {
    toggle.addEventListener('click', () => {
      sidebar.classList.toggle('open');
      overlay?.classList.toggle('open');
    });
    overlay?.addEventListener('click', () => {
      sidebar.classList.remove('open');
      overlay.classList.remove('open');
    });
  }

  // Marcar enlace activo en la nav
  const currentPath = window.location.pathname;
  document.querySelectorAll('.nav-link').forEach(link => {
    if (link.getAttribute('href') === currentPath) link.classList.add('active');
  });
});

// ── Formato de fecha ────────────────────────────────────────────
function formatDate(fechaStr, locale = 'es-BO') {
  if (!fechaStr) return '—';
  const d = new Date(fechaStr + 'T00:00:00');
  return d.toLocaleDateString(locale, { weekday:'long', year:'numeric', month:'long', day:'numeric' });
}

function formatDateShort(fechaStr) {
  if (!fechaStr) return '—';
  const [y, m, d] = fechaStr.split('-');
  return `${d}/${m}/${y}`;
}

// ── Formato de moneda ───────────────────────────────────────────
function formatBs(amount) {
  return `Bs ${parseFloat(amount || 0).toFixed(2)}`;
}

// ── Nombres de mes ──────────────────────────────────────────────
const MESES = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
               'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

// ── Confirmar acción peligrosa ──────────────────────────────────
function confirmar(mensaje) {
  return window.confirm(mensaje);
}

// ── Logout ──────────────────────────────────────────────────────
async function logout() {
  if (!confirmar('¿Deseas cerrar sesión?')) return;
  const res = await apiPost('/backend/api/auth.php', { action: 'logout' });
  if (res.success) window.location.href = _url('/index.php');
}

// ── Spinner dentro de botón ─────────────────────────────────────
function btnLoading(btn, loading = true) {
  if (loading) {
    btn.dataset.originalText = btn.innerHTML;
    btn.disabled = true;
    btn.innerHTML = '<span class="spinner" style="width:16px;height:16px;border-width:2px;"></span> Procesando...';
  } else {
    btn.disabled = false;
    btn.innerHTML = btn.dataset.originalText || btn.innerHTML;
  }
}

// ── Obtener lunes de la semana actual ───────────────────────────
function getLunesActual() {
  const hoy = new Date();
  const dia = hoy.getDay(); // 0=Dom, 1=Lun ... 6=Sáb
  // Diferencia para llegar al lunes (si hoy es dom → -6, si lun → 0, etc.)
  const diff = (dia === 0) ? -6 : 1 - dia;
  const lunes = new Date(hoy);
  lunes.setDate(hoy.getDate() + diff);
  // Usar fecha LOCAL (no UTC) para evitar desfase de zona horaria
  const y = lunes.getFullYear();
  const m = String(lunes.getMonth() + 1).padStart(2, '0');
  const d = String(lunes.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

// ── Fecha de hoy en formato YYYY-MM-DD (zona local) ──────────────
function hoy() {
  const now = new Date();
  const y = now.getFullYear();
  const m = String(now.getMonth() + 1).padStart(2, '0');
  const d = String(now.getDate()).padStart(2, '0');
  return `${y}-${m}-${d}`;
}

// ── Hora actual HH:MM ───────────────────────────────────────────
function horaActual() {
  const now = new Date();
  return now.toTimeString().substring(0, 5);
}
