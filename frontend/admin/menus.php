<?php
$pagina = 'Gestión de Menús';
require_once __DIR__ . '/layout.php';

$base = rtrim(BASE_URL, '/');

// ── Cargar menús publicados directamente desde BD (sin AJAX) ──────────────────
$menus = [];
$errorCarga = '';
try {
    $db    = Database::getConnection();
    $stmt  = $db->prepare(
        "SELECT m.id, m.fecha, m.sopa, m.plato_principal, m.postre, m.descripcion_menu, m.publicado, u.nombre AS publicado_por
         FROM menus m
         JOIN usuarios u ON u.id = m.created_by
         ORDER BY m.fecha DESC
         LIMIT 60"
    );
    $stmt->execute();
    $menus = $stmt->fetchAll();
} catch (Throwable $e) {
    $errorCarga = 'Error al cargar menús: ' . htmlspecialchars($e->getMessage());
}

$diasNombre = ['0'=>'Domingo','1'=>'Lunes','2'=>'Martes','3'=>'Miércoles',
               '4'=>'Jueves','5'=>'Viernes','6'=>'Sábado'];
?>

<!-- Cabecera con botón de acción principal -->
<div style="display:flex;align-items:center;justify-content:space-between;flex-wrap:wrap;
            gap:1rem;margin-bottom:1.5rem;">
  <div>
    <h2 style="font-size:1.1rem;color:var(--primary);margin-bottom:.2rem;">
      📋 Menús Publicados
    </h2>
    <p class="text-muted" style="font-size:.85rem;">
      Los menús se publican semana a semana desde el formulario dedicado.
    </p>
  </div>
  <a href="<?= $base ?>/backend/api/guardar_menus.php" class="btn btn-primary btn-lg">
    ➕ Publicar / Editar Menú Semanal
  </a>
</div>

<?php if ($errorCarga): ?>
  <div class="alert alert-danger"><?= $errorCarga ?></div>
<?php endif; ?>

<!-- Tabla de menús -->
<div class="card">
  <div class="card-header">
    <h3>Historial de Menús</h3>
    <span class="badge badge-muted"><?= count($menus) ?> registro(s)</span>
  </div>
  <div class="card-body" style="padding:0;">
    <?php if ($menus): ?>
      <div class="table-responsive">
        <table class="table">
          <thead>
            <tr>
              <th>Fecha</th>
              <th>Día</th>
              <th>Descripción del Menú</th>
              <th>Publicado por</th>
              <th>Acciones</th>
            </tr>
          </thead>
          <tbody>
            <?php foreach ($menus as $m): ?>
              <?php
                $dt         = DateTime::createFromFormat('Y-m-d', $m['fecha']);
                $diaSemana  = $dt ? $dt->format('N') : '0'; // 1=Lun…7=Dom
                $nombresN   = ['1'=>'Lunes','2'=>'Martes','3'=>'Miércoles',
                               '4'=>'Jueves','5'=>'Viernes','6'=>'Sábado','7'=>'Domingo'];
                $nombreDia  = $nombresN[$diaSemana] ?? '';
                $fechaCorta = $dt ? $dt->format('d/m/Y') : $m['fecha'];
              ?>
              <tr>
                <td><strong><?= $fechaCorta ?></strong></td>
                <td><?= $nombreDia ?></td>
                <td style="max-width:380px;white-space:pre-wrap;font-size:.82rem;line-height:1.5;">
                  <?= htmlspecialchars('Sopa: '.($m['sopa'] ?? '').' | Plato principal: '.($m['plato_principal'] ?? '').' | Postre: '.($m['postre'] ?? '')) ?>
                </td>
                <td style="font-size:.83rem;"><?= htmlspecialchars($m['publicado_por']) ?></td>
                <td>
                  <div style="display:flex;gap:.3rem;">
                    <!-- Editar: va al formulario con la semana correspondiente -->
                    <a href="<?= $base ?>/backend/api/guardar_menus.php?semana=<?= htmlspecialchars($m['fecha']) ?>"
                       class="btn btn-secondary btn-sm btn-icon" title="Editar semana">✏️</a>
                    <!-- Eliminar -->
                    <button class="btn btn-danger btn-sm btn-icon" title="Eliminar"
                            onclick="eliminarMenu(<?= (int)$m['id'] ?>, '<?= $fechaCorta ?>')">🗑</button>
                  </div>
                </td>
              </tr>
            <?php endforeach; ?>
          </tbody>
        </table>
      </div>
    <?php else: ?>
      <div class="empty-state" style="padding:3rem 1rem;">
        <div class="empty-icon">📭</div>
        <h4>Sin menús publicados aún</h4>
        <p>Usa el botón <strong>"Publicar / Editar Menú Semanal"</strong> para comenzar.</p>
      </div>
    <?php endif; ?>
  </div>
</div>

<div id="alertEliminar" class="mt-2"></div>

<script>
async function eliminarMenu(id, fecha) {
  if (!confirmar('¿Eliminar el menú del ' + fecha + '?')) return;

  const res = await apiPost('/backend/api/menus.php', { action: 'eliminar', menu_id: id });

  if (res.success) {
    Toast.success('Menú del ' + fecha + ' eliminado.');
    // Recargar la página para reflejar el cambio
    setTimeout(() => location.reload(), 800);
  } else {
    document.getElementById('alertEliminar').innerHTML =
      '<div class="alert alert-danger">⚠ ' + (res.message || 'Error al eliminar.') + '</div>';
  }
}
</script>

<?php require_once __DIR__ . '/layout_end.php'; ?>
