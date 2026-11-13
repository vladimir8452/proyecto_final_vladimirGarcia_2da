<?php
$pagina = 'Dashboard';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../../backend/config/database.php';

// Stats rápidas
$db = Database::getConnection();

$hoy = date('Y-m-d');
$mes = date('m');
$anio = date('Y');

// Empleados activos
$totalEmpleados = $db->query("SELECT COUNT(*) FROM usuarios WHERE tipo_cuenta != 'ADMIN' AND activo=1")->fetchColumn();
// Familiares activos
$totalFamiliares = $db->query("SELECT COUNT(*) FROM familiares WHERE estado='ACTIVO'")->fetchColumn();
// Reservas de hoy
$stmt = $db->prepare("SELECT COUNT(*) FROM reservas WHERE fecha_reserva=:h AND estado='RESERVADO'");
$stmt->execute([':h' => $hoy]);
$reservasHoy = $stmt->fetchColumn();
// Reservas del mes
$stmt2 = $db->prepare("SELECT COUNT(*) FROM reservas WHERE MONTH(fecha_reserva)=:m AND YEAR(fecha_reserva)=:a AND estado='RESERVADO'");
$stmt2->execute([':m' => $mes, ':a' => $anio]);
$reservasMes = $stmt2->fetchColumn();
// Menú de hoy
$stmtM = $db->prepare("SELECT descripcion_menu FROM menus WHERE fecha=:h AND publicado=1");
$stmtM->execute([':h' => $hoy]);
$menuHoy = $stmtM->fetchColumn();
// Feriados próximos
$stmtF = $db->prepare("SELECT fecha, descripcion_feriado FROM calendario_operativo WHERE es_feriado=1 AND fecha >= :h ORDER BY fecha ASC LIMIT 3");
$stmtF->execute([':h' => $hoy]);
$feriadosProximos = $stmtF->fetchAll();
// Últimas reservas
$ultimasReservas = $db->prepare("SELECT r.fecha_reserva, r.tipo_beneficiario, r.estado, u.nombre AS titular, IF(r.tipo_beneficiario='FAMILIAR',COALESCE(f.nombre,'Invitado'),NULL) AS familiar FROM reservas r JOIN usuarios u ON u.id=r.usuario_id LEFT JOIN familiares f ON f.id=r.familiar_id ORDER BY r.fecha_creacion DESC LIMIT 8");
$ultimasReservas->execute();
$ultimasReservas = $ultimasReservas->fetchAll();
?>

<!-- Stats Grid -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon icon-blue">👥</div>
    <div class="stat-info">
      <h4><?= $totalEmpleados ?></h4>
      <p>Empleados Activos</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon icon-purple">👨‍👩‍👧</div>
    <div class="stat-info">
      <h4><?= $totalFamiliares ?></h4>
      <p>Familiares Registrados</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon icon-green">📅</div>
    <div class="stat-info">
      <h4><?= $reservasHoy ?></h4>
      <p>Reservas Hoy</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon icon-orange">📊</div>
    <div class="stat-info">
      <h4><?= $reservasMes ?></h4>
      <p>Reservas Este Mes</p>
    </div>
  </div>
</div>

<!-- Fila principal -->
<div style="display:grid;grid-template-columns:2fr 1fr;gap:1.5rem;">

  <!-- Menú del día + Reservas recientes -->
  <div>
    <!-- Menú de hoy -->
    <div class="menu-day-card mb-2">
      <div class="fecha-label">📋 Menú de Hoy — <?= date('l d/m/Y', strtotime($hoy)) ?></div>
      <?php if ($menuHoy): ?>
        <h2>Almuerzo del día</h2>
        <p class="menu-desc"><?= nl2br(htmlspecialchars($menuHoy)) ?></p>
      <?php else: ?>
        <h2>Sin menú publicado</h2>
        <p class="menu-desc" style="opacity:.7;">
          <a href="<?= $base ?>/frontend/admin/menus.php" style="color:#fff;text-decoration:underline;">Publicar menú de hoy →</a>
        </p>
      <?php endif; ?>
    </div>

    <!-- Últimas reservas -->
    <div class="card">
      <div class="card-header">
        <h3>Últimas Reservas</h3>
        <a href="<?= $base ?>/frontend/admin/reservas.php" class="btn btn-secondary btn-sm">Ver todas</a>
      </div>
      <div class="card-body" style="padding:0;">
        <?php if ($ultimasReservas): ?>
          <div class="table-responsive">
            <table class="table">
              <thead>
                <tr>
                  <th>Fecha</th>
                  <th>Titular</th>
                  <th>Para</th>
                  <th>Estado</th>
                </tr>
              </thead>
              <tbody>
                <?php foreach ($ultimasReservas as $r): ?>
                  <tr>
                    <td><?= date('d/m/Y', strtotime($r['fecha_reserva'])) ?></td>
                    <td><?= htmlspecialchars($r['titular']) ?></td>
                    <td>
                      <?php if ($r['tipo_beneficiario'] === 'FAMILIAR'): ?>
                        <span class="badge badge-info"><?= htmlspecialchars($r['familiar'] ?? 'Familiar') ?></span>
                      <?php else: ?>
                        <span class="badge badge-primary">Titular</span>
                      <?php endif; ?>
                    </td>
                    <td>
                      <?php if ($r['estado'] === 'RESERVADO'): ?>
                        <span class="badge badge-success">Reservado</span>
                      <?php else: ?>
                        <span class="badge badge-danger">Cancelado</span>
                      <?php endif; ?>
                    </td>
                  </tr>
                <?php endforeach; ?>
              </tbody>
            </table>
          </div>
        <?php else: ?>
          <div class="empty-state">
            <div class="empty-icon">📭</div>
            <h4>Sin reservas aún</h4>
          </div>
        <?php endif; ?>
      </div>
    </div>
  </div>

  <!-- Panel lateral -->
  <div>
    <!-- Accesos rápidos -->
    <div class="card mb-2">
      <div class="card-header"><h3>Acciones Rápidas</h3></div>
      <div class="card-body" style="display:flex;flex-direction:column;gap:.6rem;">
        <a href="<?= $base ?>/frontend/admin/menus.php" class="btn btn-primary">📋 Publicar Menú</a>
        <a href="<?= $base ?>/frontend/admin/empleados.php" class="btn btn-outline-primary">👥 Registrar Empleado</a>
        <a href="<?= $base ?>/frontend/admin/calendario.php" class="btn btn-warning">🗓️ Gestionar Feriados</a>
        <a href="<?= $base ?>/frontend/admin/reportes.php" class="btn btn-success">📈 Generar Reporte</a>
      </div>
    </div>

    <!-- Feriados próximos -->
    <div class="card">
      <div class="card-header"><h3>Feriados Próximos</h3></div>
      <div class="card-body" style="padding:0;">
        <?php if ($feriadosProximos): ?>
          <?php foreach ($feriadosProximos as $f): ?>
            <div class="reserva-item">
              <div>
                <div class="fecha"><?= date('d/m/Y', strtotime($f['fecha'])) ?></div>
                <div class="tipo"><?= htmlspecialchars($f['descripcion_feriado'] ?? 'Feriado') ?></div>
              </div>
              <span class="badge badge-warning">Feriado</span>
            </div>
          <?php endforeach; ?>
        <?php else: ?>
          <p class="text-muted text-center" style="padding:1rem;font-size:.85rem;">Sin feriados próximos</p>
        <?php endif; ?>
      </div>
    </div>
  </div>

</div>

<?php require_once __DIR__ . '/layout_end.php'; ?>
