<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/session.php';
requireRole('ADMIN');

$pagina  = $pagina ?? 'Dashboard';
$nombre  = $_SESSION['nombre'] ?? 'Admin';
$inicial = strtoupper(mb_substr($nombre, 0, 1));
$base    = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pagina) ?> — Admin · Rumbo al Cielo</title>
  <link rel="stylesheet" href="<?= $base ?>/frontend/assets/css/main.css">
  <!-- BASE_URL y app.js van en el <head> para que estén disponibles
       antes de que cualquier <script> inline de la página se ejecute -->
  <script>const BASE_URL = <?= json_encode($base) ?>;</script>
  <script src="<?= $base ?>/frontend/assets/js/app.js"></script>
  <script>
  // ── Notificaciones del administrador: solicitudes de recuperación de contraseña ──
  (function () {
    const esc = s => String(s ?? '').replace(/[&<>"']/g, c => ({'&':'&amp;','<':'&lt;','>':'&gt;','"':'&quot;',"'":'&#39;'}[c]));
    let pendientes = [];

    async function cargarNotificaciones() {
      try {
        const r = await apiGet('/backend/api/notificaciones.php', {action: 'pendientes'});
        pendientes = (r && r.data) || [];
      } catch (e) { pendientes = []; }
      const b = document.getElementById('notifBadge');
      if (b) { b.textContent = pendientes.length; b.style.display = pendientes.length ? 'inline-block' : 'none'; }
      if (pendientes.length) {
        const ultimo = Math.max(...pendientes.map(p => Number(p.id)));
        if (ultimo > Number(sessionStorage.getItem('notifRecUltimo') || 0)) {
          sessionStorage.setItem('notifRecUltimo', String(ultimo));
          Toast.warning('🔔 ' + pendientes.length + ' empleado(s) desea(n) recuperar su contraseña. Revisa las notificaciones.');
        }
      }
    }

    window.abrirNotificaciones = function () {
      document.getElementById('modalNotif')?.remove();
      const filas = pendientes.length ? pendientes.map(p => `
        <div style="border:1px solid #d6dde6;border-radius:8px;padding:.7rem .85rem;margin-bottom:.6rem;">
          <strong>${esc(p.nombre || 'Empleado no identificado')}</strong>${p.codigo_empleado ? ' · ' + esc(p.codigo_empleado) : ''}<br>
          <span style="font-size:.82rem;color:#5d6d7e;">${esc(p.identificador)} · ${esc(p.solicitado_en)}</span><br>
          <span style="font-size:.85rem;">desea recuperar su contraseña.</span>
          <div style="margin-top:.5rem;display:flex;gap:.5rem;">
            <a class="btn btn-secondary btn-sm" href="<?= $base ?>/frontend/admin/empleados.php">Ir a Empleados</a>
            <button class="btn btn-primary btn-sm" onclick="atenderSolicitud(${Number(p.id)})">Marcar atendida</button>
          </div>
        </div>`).join('') : '<p style="color:#5d6d7e;">No hay solicitudes pendientes.</p>';
      const w = document.createElement('div');
      w.id = 'modalNotif';
      w.style.cssText = 'position:fixed;inset:0;background:rgba(0,0,0,.45);display:grid;place-items:center;z-index:9999;';
      w.innerHTML = `<div style="background:#fff;border-radius:12px;width:min(480px,92vw);max-height:80vh;overflow:auto;padding:1.2rem;">
          <h4 style="margin:0 0 .3rem;">🔔 Solicitudes de recuperación de contraseña</h4>
          <p style="font-size:.8rem;color:#5d6d7e;margin:0 0 .8rem;">Para ayudar al empleado, edítalo en <em>Empleados</em> y asígnale una nueva contraseña; luego marca la solicitud como atendida.</p>
          ${filas}
          <div style="text-align:right;"><button class="btn btn-secondary btn-sm" onclick="document.getElementById('modalNotif').remove()">Cerrar</button></div>
        </div>`;
      w.addEventListener('click', e => { if (e.target === w) w.remove(); });
      document.body.appendChild(w);
    };

    window.atenderSolicitud = async function (id) {
      await apiPost('/backend/api/notificaciones.php', {action: 'atender', id});
      await cargarNotificaciones();
      abrirNotificaciones();
    };

    document.addEventListener('DOMContentLoaded', cargarNotificaciones);
  })();
  </script>
</head>
<body>
<div class="app-wrapper">

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.15);
                  display:grid;place-items:center;font-size:1.8rem;margin:0 auto .5rem;">🍽️</div>
      <h2>Rumbo al Cielo</h2>
      <p>Panel Administrador</p>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-title">Principal</span>
      <a href="<?= $base ?>/frontend/admin/dashboard.php" class="nav-link">
        <span class="icon">📊</span> Dashboard
      </a>
      <a href="<?= $base ?>/frontend/admin/empleados.php" class="nav-link">
        <span class="icon">👥</span> Empleados
      </a>
      <a href="<?= $base ?>/frontend/admin/familiares.php" class="nav-link">
        <span class="icon">👨‍👩‍👧</span> Familiares
      </a>

      <span class="nav-section-title">Operación</span>
      <a href="<?= $base ?>/frontend/admin/platos.php" class="nav-link">
        <span class="icon">🍽️</span> Platos
      </a>
      <a href="<?= $base ?>/frontend/admin/menus.php" class="nav-link">
        <span class="icon">📋</span> Menús
      </a>
      <a href="<?= $base ?>/frontend/admin/cocina.php" class="nav-link"><span class="icon">🍲</span> Control de Cocina</a>
      <a href="<?= $base ?>/frontend/admin/reservas.php" class="nav-link">
        <span class="icon">📅</span> Reservas del Día
      </a>
      <a href="<?= $base ?>/frontend/admin/configuracion.php" class="nav-link"><span class="icon">⚙️</span> Configuración</a>
      <a href="<?= $base ?>/frontend/admin/calendario.php" class="nav-link">
        <span class="icon">🗓️</span> Calendario / Feriados
      </a>

      <span class="nav-section-title">Reportes</span>
      <a href="<?= $base ?>/frontend/admin/reportes.php" class="nav-link">
        <span class="icon">📈</span> Reporte Mensual
      </a>
    </nav>

    <div class="sidebar-footer">
      <strong><?= htmlspecialchars($nombre) ?></strong><br>
      Administrador del sistema
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:.75rem;">
        <button class="sidebar-toggle" id="sidebarToggle" aria-label="Menú">☰</button>
        <h1><?= htmlspecialchars($pagina) ?></h1>
      </div>
      <div class="topbar-right">
        <button class="btn btn-secondary btn-sm" id="btnNotif" onclick="abrirNotificaciones()" title="Notificaciones" style="position:relative;">
          🔔<span id="notifBadge" style="display:none;position:absolute;top:-6px;right:-6px;background:#c0392b;color:#fff;border-radius:999px;font-size:.65rem;min-width:18px;height:18px;line-height:18px;text-align:center;padding:0 4px;">0</span>
        </button>
        <div class="user-badge">
          <div class="avatar"><?= $inicial ?></div>
          <span><?= htmlspecialchars($nombre) ?></span>
        </div>
        <button class="btn btn-secondary btn-sm" onclick="logout()">Salir</button>
      </div>
    </header>
    <main class="page-content">
