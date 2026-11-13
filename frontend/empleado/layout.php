<?php
require_once __DIR__ . '/../../backend/config/database.php';
require_once __DIR__ . '/../../backend/config/session.php';
requireRole(['PENSIONADO', 'NO_PENSIONADO']);

$pagina      = $pagina ?? 'Mi Panel';
$nombre      = $_SESSION['nombre'] ?? 'Empleado';
$tipoCuenta  = $_SESSION['tipo_cuenta'] ?? '';
$inicial     = strtoupper(mb_substr($nombre, 0, 1));
$esPensionado = $tipoCuenta === 'PENSIONADO';
$base         = rtrim(BASE_URL, '/');
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title><?= htmlspecialchars($pagina) ?> — Rumbo al Cielo</title>
  <link rel="stylesheet" href="<?= $base ?>/frontend/assets/css/main.css">
  <!-- BASE_URL y app.js en el <head> → disponibles antes de cualquier script de página -->
  <script>const BASE_URL = <?= json_encode($base) ?>;</script>
  <script src="<?= $base ?>/frontend/assets/js/app.js"></script>
</head>
<body>
<div class="app-wrapper">

  <div class="sidebar-overlay" id="sidebarOverlay"></div>

  <aside class="sidebar" id="sidebar">
    <div class="sidebar-brand">
      <div style="width:56px;height:56px;border-radius:50%;background:rgba(255,255,255,.15);
                  display:grid;place-items:center;font-size:1.8rem;margin:0 auto .5rem;">🍽️</div>
      <h2>Rumbo al Cielo</h2>
      <p><?= $esPensionado ? 'Empleado Pensionado' : 'Empleado No Pensionado' ?></p>
    </div>

    <nav class="sidebar-nav">
      <span class="nav-section-title">Mi Panel</span>
      <a href="<?= $base ?>/frontend/empleado/dashboard.php" class="nav-link">
        <span class="icon">🏠</span> Inicio
      </a>
      <a href="<?= $base ?>/frontend/empleado/menu.php" class="nav-link">
        <span class="icon">📋</span> Menú Semanal
      </a>
      <a href="<?= $base ?>/frontend/empleado/reservas.php" class="nav-link">
        <span class="icon">📅</span> Mis Reservas
      </a>
      <?php if ($esPensionado): ?>
      <a href="<?= $base ?>/frontend/empleado/pension.php" class="nav-link"><span class="icon">🍽️</span> Mi Pensión Mensual</a>
      <span class="nav-section-title">Familia</span>
      <a href="<?= $base ?>/frontend/empleado/familiares.php" class="nav-link">
        <span class="icon">👨‍👩‍👧</span> Mis Familiares
      </a>
      <?php endif; ?>
    </nav>

    <div class="sidebar-footer">
      <strong><?= htmlspecialchars($nombre) ?></strong><br>
      <?= $tipoCuenta === 'PENSIONADO' ? 'Pensionado · 15 Bs/almuerzo' : 'No Pensionado · 18 Bs/almuerzo' ?>
    </div>
  </aside>

  <div class="main-content">
    <header class="topbar">
      <div style="display:flex;align-items:center;gap:.75rem;">
        <button class="sidebar-toggle" id="sidebarToggle">☰</button>
        <h1><?= htmlspecialchars($pagina) ?></h1>
      </div>
      <div class="topbar-right">
        <div class="user-badge">
          <div class="avatar"><?= $inicial ?></div>
          <div>
            <div style="font-weight:600;font-size:.88rem;"><?= htmlspecialchars($nombre) ?></div>
            <div style="font-size:.74rem;color:var(--muted);">
              <?= $esPensionado
                ? '<span class="badge badge-info" style="font-size:.68rem;">Pensionado</span>'
                : '<span class="badge badge-warning" style="font-size:.68rem;">No Pensionado</span>' ?>
            </div>
          </div>
        </div>
        <button class="btn btn-secondary btn-sm" onclick="logout()">Salir</button>
      </div>
    </header>
    <main class="page-content">
