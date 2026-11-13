<?php
$pagina = 'Mi Panel';
require_once __DIR__ . '/layout.php';
require_once __DIR__ . '/../../backend/config/database.php';

$usuarioId  = (int) $_SESSION['usuario_id'];
$tipoCuenta = $_SESSION['tipo_cuenta'];
$hoy        = date('Y-m-d');
$mes        = (int) date('m');
$anio       = (int) date('Y');
$db         = Database::getConnection();

// Reservas este mes
$stmtMes = $db->prepare(
    "SELECT COUNT(*) FROM reservas
     WHERE usuario_id=:uid AND tipo_beneficiario='TITULAR'
       AND MONTH(fecha_reserva)=:m AND YEAR(fecha_reserva)=:a
       AND estado='RESERVADO'"
);
$stmtMes->execute([':uid'=>$usuarioId, ':m'=>$mes, ':a'=>$anio]);
$reservasMesTitular = (int) $stmtMes->fetchColumn();

// Reserva de hoy
$stmtHoy = $db->prepare(
    "SELECT id FROM reservas
     WHERE usuario_id=:uid AND tipo_beneficiario='TITULAR'
       AND fecha_reserva=:h AND estado='RESERVADO' LIMIT 1"
);
$stmtHoy->execute([':uid'=>$usuarioId, ':h'=>$hoy]);
$reservaHoy = $stmtHoy->fetch();

// Menú de hoy
$stmtMenu = $db->prepare("SELECT * FROM menus WHERE fecha=:h AND publicado=1 LIMIT 1");
$stmtMenu->execute([':h'=>$hoy]);
$menuHoy = $stmtMenu->fetch();

// ¿Hoy es operativo?
require_once __DIR__ . '/../../backend/models/Calendario.php';
$calendarioModel = new Calendario();
$esOperativo = $calendarioModel->esDiaOperativo($hoy);

// Próximas reservas (5)
$stmtProx = $db->prepare(
    "SELECT r.*, f.nombre AS familiar_nombre
     FROM reservas r
     LEFT JOIN familiares f ON f.id=r.familiar_id
     WHERE r.usuario_id=:uid AND r.fecha_reserva >= :h AND r.estado='RESERVADO'
     ORDER BY r.fecha_reserva ASC LIMIT 5"
);
$stmtProx->execute([':uid'=>$usuarioId, ':h'=>$hoy]);
$proximasReservas = $stmtProx->fetchAll();

// ¿Está inscrito en la pensión de este mes? Solo entonces aplica el mínimo de almuerzos.
$stmtInsc = $db->prepare(
    "SELECT COUNT(*) FROM inscripciones_pension
     WHERE usuario_id=:uid AND beneficiario_id=0 AND anio=:a AND mes=:m AND estado='ACTIVA'"
);
$stmtInsc->execute([':uid'=>$usuarioId, ':a'=>$anio, ':m'=>$mes]);
$inscritoMes = (int) $stmtInsc->fetchColumn() > 0;

// Tarifa
$tarifa = $inscritoMes ? TARIFA_PENSIONADO : TARIFA_NO_PENSIONADO;
$costoEstimado = $reservasMesTitular * $tarifa;
?>

<!-- Bienvenida -->
<div style="background:linear-gradient(135deg,var(--primary) 0%,var(--primary-light) 100%);
            border-radius:var(--radius);padding:1.5rem;color:#fff;margin-bottom:1.5rem;
            display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;gap:1rem;">
  <div>
    <h2 style="font-size:1.3rem;">¡Hola, <?= htmlspecialchars(explode(' ', $nombre)[0]) ?>!</h2>
    <p style="opacity:.8;margin-top:.2rem;font-size:.9rem;">
      <?= date('l, d \d\e F \d\e Y') ?>
    </p>
  </div>
  <?php if ($esOperativo && $menuHoy && !$reservaHoy): ?>
    <button class="btn btn-warning btn-lg" onclick="reservarHoy()">
      🍽️ Reservar Almuerzo de Hoy
    </button>
  <?php elseif ($reservaHoy): ?>
    <div style="background:rgba(255,255,255,.15);padding:.8rem 1.2rem;border-radius:8px;">
      <strong>✅ Ya tienes reserva para hoy</strong>
    </div>
  <?php elseif (!$esOperativo): ?>
    <div style="background:rgba(255,255,255,.1);padding:.8rem 1.2rem;border-radius:8px;font-size:.88rem;">
      📵 Hoy no es día operativo
    </div>
  <?php else: ?>
    <div style="background:rgba(255,255,255,.1);padding:.8rem 1.2rem;border-radius:8px;font-size:.88rem;">
      📋 Sin menú publicado para hoy
    </div>
  <?php endif; ?>
</div>

<!-- Stats -->
<div class="stats-grid">
  <div class="stat-card">
    <div class="stat-icon icon-green">🍽️</div>
    <div class="stat-info">
      <h4><?= $reservasMesTitular ?></h4>
      <p>Almuerzos Este Mes</p>
    </div>
  </div>
  <?php if ($inscritoMes): ?>
    <div class="stat-card">
      <div class="stat-icon icon-orange">⚠</div>
      <div class="stat-info">
        <h4><?= max(0, MINIMO_ALMUERZOS_MES - $reservasMesTitular) ?></h4>
        <p>Almuerzos Faltantes para Mínimo</p>
      </div>
    </div>
  <?php endif; ?>
  <div class="stat-card">
    <div class="stat-icon icon-blue">💰</div>
    <div class="stat-info">
      <h4>Bs <?= number_format($costoEstimado, 2) ?></h4>
      <p>Costo Estimado Mes (titular)</p>
    </div>
  </div>
  <div class="stat-card">
    <div class="stat-icon icon-purple">📅</div>
    <div class="stat-info">
      <h4><?= count($proximasReservas) ?></h4>
      <p>Reservas Próximas</p>
    </div>
  </div>
</div>

<?php if ($inscritoMes && $reservasMesTitular < MINIMO_ALMUERZOS_MES): ?>
<div class="alert alert-warning">
  ⚠ Tienes <?= $reservasMesTitular ?> almuerzo(s) este mes. El mínimo obligatorio es <?= MINIMO_ALMUERZOS_MES ?>.
  Si no alcanzas el mínimo, se te cobrarán <strong>Bs <?= MINIMO_ALMUERZOS_MES * TARIFA_PENSIONADO ?></strong> (<?= MINIMO_ALMUERZOS_MES ?> × Bs <?= TARIFA_PENSIONADO ?>).
</div>
<?php endif; ?>

<div style="display:grid;grid-template-columns:1.2fr 1fr;gap:1.5rem;">

  <!-- Menú de hoy -->
  <div class="card">
    <div class="card-header">
      <h3>📋 Menú de Hoy</h3>
      <a href="<?= $base ?>/frontend/empleado/menu.php" class="btn btn-secondary btn-sm">Ver semana →</a>
    </div>
    <div class="card-body">
      <?php if ($menuHoy): ?>
        <div style="font-size:.82rem;color:var(--muted);margin-bottom:.5rem;">
          <?= date('l d/m/Y', strtotime($hoy)) ?>
        </div>
        <p style="line-height:1.7;"><?= nl2br(htmlspecialchars($menuHoy['descripcion_menu'])) ?></p>
        <div class="mt-2">
          <span class="badge badge-info">Bs <?= $tarifa ?> por almuerzo</span>
        </div>
      <?php else: ?>
        <div class="empty-state" style="padding:1.5rem 0;">
          <div class="empty-icon">📭</div>
          <h4>Sin menú para hoy</h4>
          <p>El menú aún no ha sido publicado.</p>
        </div>
      <?php endif; ?>
    </div>
  </div>

  <!-- Próximas reservas -->
  <div class="card">
    <div class="card-header">
      <h3>📅 Próximas Reservas</h3>
      <a href="<?= $base ?>/frontend/empleado/reservas.php" class="btn btn-secondary btn-sm">Ver todas →</a>
    </div>
    <div class="card-body" style="padding:0;">
      <?php if ($proximasReservas): ?>
        <?php foreach ($proximasReservas as $r): ?>
          <div class="reserva-item">
            <div class="reserva-info">
              <div class="fecha"><?= date('d/m/Y', strtotime($r['fecha_reserva'])) ?></div>
              <div class="tipo">
                <?= $r['tipo_beneficiario'] === 'FAMILIAR'
                  ? '👨‍👩‍👧 ' . htmlspecialchars($r['familiar_nombre'] ?? 'Familiar')
                  : '👤 Titular' ?>
              </div>
            </div>
            <span class="badge badge-success">Reservado</span>
          </div>
        <?php endforeach; ?>
      <?php else: ?>
        <div class="empty-state" style="padding:1.5rem 0;">
          <div class="empty-icon">📭</div>
          <h4>Sin reservas próximas</h4>
        </div>
      <?php endif; ?>
    </div>
  </div>

</div>

<div id="alertDash" class="mt-2"></div>

<script>
async function reservarHoy() {
  const hoy = '<?= $hoy ?>';
  const alertEl = document.getElementById('alertDash');
  alertEl.innerHTML = '';

  const res = await apiPost('/backend/api/reservas.php', { action: 'reservar_titular', fecha: hoy });
  if (res.success) {
    Toast.success(res.message);
    alertEl.innerHTML = '<div class="alert alert-success">✅ ' + res.message + ' Actualiza la página para ver los cambios.</div>';
    document.querySelector('[onclick="reservarHoy()"]')?.remove();
  } else {
    alertEl.innerHTML = '<div class="alert alert-danger">⚠ ' + res.message + '</div>';
  }
}
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
