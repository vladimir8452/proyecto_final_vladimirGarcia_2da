<?php
/**
 * Endpoint directo de guardado de menús — sin AJAX, POST puro.
 * Diseñado para máxima transparencia: cualquier error SQL se imprime
 * en pantalla para facilitar depuración inmediata.
 *
 * Restaurante "Rumbo al Cielo" — Unión Boliviana
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../../src/autoload.php';
require_once __DIR__ . '/../models/Menu.php';
require_once __DIR__ . '/../models/Calendario.php';
require_once __DIR__ . '/../models/PlatoCatalogo.php';
require_once __DIR__ . '/../config/session.php';
// Solo administradores
requireRole('ADMIN');

$base    = rtrim(BASE_URL, '/');
$adminId = (int) $_SESSION['usuario_id'];
$errores = [];
$exitos  = [];

// ── Catálogo y publicación mediante caso de uso ───────────────────────────────
$todosPlatos = [];
$errores = [];
$exitos = [];
try {
    $catalogo = new PlatoCatalogo();
    $todosPlatos = $catalogo->listarCatalogo();
} catch (Throwable $e) {
    $errores[] = 'No se pudo cargar el catálogo de platos: <code>' . htmlspecialchars($e->getMessage()) . '</code>';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $selecciones = $_POST['menus'] ?? [];
    if (empty($selecciones) || !is_array($selecciones)) {
        $errores[] = 'No se recibió ningún dato. Asegúrate de llenar al menos un día.';
    } else {
        try {
            $casoUso = \RumboAlCielo\Infrastructure\Contenedor::publicarMenuSemanal();
            $resultado = $casoUso->ejecutar($selecciones, $adminId);
            $errores = array_merge($errores, $resultado['errores']);
            $exitos = array_merge($exitos, $resultado['exitos']);
        } catch (Throwable $e) {
            $errores[] = '<strong>Error al publicar el menú:</strong><br><code>' . htmlspecialchars($e->getMessage()) . '</code>';
        }
    }
}

// ── Calcular semana seleccionada para el formulario ───────────────────────────
// Tomar el lunes de la semana indicada (GET ?semana=YYYY-MM-DD) o la semana actual
$semanaParam = $_GET['semana'] ?? ($_POST['semana_base'] ?? '');
if ($semanaParam) {
    $dtLunes = DateTime::createFromFormat('Y-m-d', $semanaParam);
    if (!$dtLunes || $dtLunes->format('N') != 1) {
        // Si no es lunes, retroceder al lunes de esa semana
        $dtLunes = new DateTime($semanaParam);
        $dtLunes->modify('monday this week');
    }
} else {
    $dtLunes = new DateTime();
    $diaN = (int) $dtLunes->format('N');
    if ($diaN !== 1) $dtLunes->modify('last monday');
    if ($diaN === 7) $dtLunes->modify('+1 day'); // Si es domingo, avanzar al próximo lunes
}

// Los 4 días operativos de la semana
$diasSemana = [];
for ($i = 0; $i < 4; $i++) {
    $dt = clone $dtLunes;
    $dt->modify("+{$i} days");
    $diasSemana[] = $dt;
}

$lunesStr  = $dtLunes->format('Y-m-d');
$juevesStr = $diasSemana[3]->format('Y-m-d');

// ── Cargar menús ya guardados para esta semana (para pre-rellenar) ─────────────
$menusPrevios = [];
try {
    $menuModel = new Menu();
    foreach ($menuModel->listarSemana($lunesStr) as $row) {
        if ($row['fecha'] < $lunesStr || $row['fecha'] > $juevesStr) continue;
        $menusPrevios[$row['fecha']] = [
            'sopa_id' => (int)($row['sopa_id'] ?? 0),
            'principal_id' => (int)($row['principal_id'] ?? 0),
            'postre_id' => (int)($row['postre_id'] ?? 0),
            'sopa' => $row['sopa'] ?? '',
            'plato_principal' => $row['plato_principal'] ?? '',
            'postre' => $row['postre'] ?? '',
            'descripcion_menu' => $row['descripcion_menu'] ?? '',
        ];
    }
} catch (Throwable $e) {
    $errores[] = 'Error al cargar menús previos: <code>' . htmlspecialchars($e->getMessage()) . '</code>';
}

// ── Semana anterior / siguiente para navegación ────────────────────────────────
$dtAnterior  = clone $dtLunes; $dtAnterior->modify('-7 days');
$dtSiguiente = clone $dtLunes; $dtSiguiente->modify('+7 days');

$nombresDia = ['Lunes', 'Martes', 'Miércoles', 'Jueves'];
$meses = ['','Enero','Febrero','Marzo','Abril','Mayo','Junio',
          'Julio','Agosto','Septiembre','Octubre','Noviembre','Diciembre'];

/** Opciones <option> de los platos de un tipo; conserva el ya elegido aunque esté inactivo. */
function opcionesPlato(array $todos, string $tipo, int $seleccionado): string {
    $html = '<option value="0">— Selecciona —</option>';
    foreach ($todos as $p) {
        if ($p['tipo'] !== $tipo) continue;
        $activo = (int) $p['activo'] === 1;
        if (!$activo && (int) $p['id'] !== $seleccionado) continue;
        $sel = (int) $p['id'] === $seleccionado ? ' selected' : '';
        $html .= '<option value="' . (int) $p['id'] . '"' . $sel . '>'
               . htmlspecialchars($p['nombre']) . ($activo ? '' : ' (inactivo)') . '</option>';
    }
    return $html;
}
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Publicar Menú Semanal — Admin</title>
  <link rel="stylesheet" href="<?= $base ?>/frontend/assets/css/main.css">
  <style>
    body { background: var(--bg); }
    .wrap { max-width: 860px; margin: 2rem auto; padding: 0 1rem; }
    .nav-sem {
      display: flex; align-items: center; justify-content: space-between;
      background: var(--primary); color: #fff;
      padding: .85rem 1.25rem; border-radius: var(--radius) var(--radius) 0 0;
    }
    .nav-sem a { color: #fff; font-size: .9rem; text-decoration: none;
                 background: rgba(255,255,255,.15); padding: .4rem .9rem;
                 border-radius: 6px; transition: background .2s; }
    .nav-sem a:hover { background: rgba(255,255,255,.28); }
    .nav-sem h2 { font-size: 1rem; font-weight: 600; }
    .dia-row { padding: 1rem 1.25rem; border-bottom: 1px solid var(--border); }
    .dia-row:last-child { border-bottom: none; }
    .dia-label {
      display: flex; align-items: center; gap: .6rem;
      font-weight: 600; font-size: .95rem; margin-bottom: .5rem; color: var(--primary);
    }
    .dia-fecha { font-size: .8rem; color: var(--muted); font-weight: 400; }
    .dia-row textarea {
      width: 100%; padding: .6rem .85rem;
      border: 1.5px solid var(--border); border-radius: 7px;
      font-size: .88rem; font-family: inherit; resize: vertical; min-height: 78px;
      transition: border-color .2s, box-shadow .2s;
    }
    .dia-row textarea:focus {
      outline: none; border-color: var(--primary-light);
      box-shadow: 0 0 0 3px rgba(46,134,193,.15);
    }
    .dia-row.tiene-menu textarea { border-color: #22c55e; background: #f0fdf4; }
    .actions { padding: 1rem 1.25rem; display: flex; gap: .75rem; align-items: center;
               border-top: 1px solid var(--border); background: #f8fafc;
               border-radius: 0 0 var(--radius) var(--radius); }
    .alert-box { padding: .85rem 1.1rem; border-radius: 8px; margin-bottom: 1rem; font-size: .88rem; }
    .alert-ok  { background: #dcfce7; color: #15803d; border-left: 4px solid #22c55e; }
    .alert-err { background: #fee2e2; color: #b91c1c; border-left: 4px solid #ef4444; }
    .back-link { display:inline-flex; align-items:center; gap:.4rem; margin-bottom:1rem;
                 font-size:.88rem; color: var(--primary); }
  </style>
</head>
<body>
<div class="wrap">

  <a href="<?= $base ?>/frontend/admin/menus.php" class="back-link">← Volver al Panel de Menús</a>
  &nbsp;·&nbsp;
  <a href="<?= $base ?>/frontend/admin/platos.php" class="back-link">🍽️ Gestionar catálogo de platos</a>

  <h1 style="font-size:1.3rem;color:var(--primary);margin-bottom:1rem;">
    📋 Publicar Menú Semanal
  </h1>

  <?php if ($errores): ?>
    <div class="alert-box alert-err">
      <strong>⚠ Se encontraron los siguientes errores:</strong>
      <ul style="margin:.5rem 0 0 1.2rem;">
        <?php foreach ($errores as $e): ?>
          <li><?= $e ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <?php if ($exitos): ?>
    <div class="alert-box alert-ok">
      <strong>✓ Guardado correctamente (<?= count($exitos) ?> día(s)):</strong>
      <ul style="margin:.5rem 0 0 1.2rem;">
        <?php foreach ($exitos as $ex): ?>
          <li><?= htmlspecialchars($ex) ?></li>
        <?php endforeach; ?>
      </ul>
    </div>
  <?php endif; ?>

  <form method="POST" action="<?= $base ?>/backend/api/guardar_menus.php">
    <!-- Semana base para conservar la navegación al hacer POST -->
    <input type="hidden" name="semana_base" value="<?= htmlspecialchars($lunesStr) ?>">

    <div class="card" style="border-radius:var(--radius);">

      <!-- Navegación de semana -->
      <div class="nav-sem">
        <a href="?semana=<?= $dtAnterior->format('Y-m-d') ?>">← Semana anterior</a>
        <h2>
          Semana del <?= $dtLunes->format('d') ?> al <?= $diasSemana[3]->format('d') ?>
          de <?= $meses[(int)$dtLunes->format('n')] ?> <?= $dtLunes->format('Y') ?>
        </h2>
        <a href="?semana=<?= $dtSiguiente->format('Y-m-d') ?>">Semana siguiente →</a>
      </div>

      <!-- Un bloque por cada día L–J -->
      <?php foreach ($diasSemana as $idx => $dtDia): ?>
        <?php
          $fechaDia   = $dtDia->format('Y-m-d');
          $menuPrevio = $menusPrevios[$fechaDia] ?? ['sopa_id'=>0,'principal_id'=>0,'postre_id'=>0,'sopa'=>'','plato_principal'=>'','postre'=>'','descripcion_menu'=>''];
          $tieneMenu  = ($menuPrevio['sopa'] !== '' || $menuPrevio['plato_principal'] !== '' || $menuPrevio['postre'] !== '' || $menuPrevio['descripcion_menu'] !== '');
        ?>
        <div class="dia-row <?= $tieneMenu ? 'tiene-menu' : '' ?>">
          <div class="dia-label">
            <span><?= $nombresDia[$idx] ?></span>
            <span class="dia-fecha"><?= $dtDia->format('d/m/Y') ?></span>
            <?php if ($tieneMenu): ?>
              <span style="font-size:.72rem;background:#dcfce7;color:#15803d;
                           padding:.15rem .55rem;border-radius:999px;font-weight:600;">
                ✓ Ya publicado
              </span>
            <?php else: ?>
              <span style="font-size:.72rem;background:#f1f5f9;color:#64748b;
                           padding:.15rem .55rem;border-radius:999px;">
                Sin menú
              </span>
            <?php endif; ?>
          </div>
          <div style="display:grid;grid-template-columns:repeat(3,minmax(0,1fr));gap:.75rem;padding:0 1rem 1rem;">
            <label>Sopa<select class="form-control" name="menus[<?= $fechaDia ?>][sopa_id]"><?= opcionesPlato($todosPlatos, 'SOPA', (int) $menuPrevio['sopa_id']) ?></select></label>
            <label>Plato principal<select class="form-control" name="menus[<?= $fechaDia ?>][principal_id]"><?= opcionesPlato($todosPlatos, 'PRINCIPAL', (int) $menuPrevio['principal_id']) ?></select></label>
            <label>Postre<select class="form-control" name="menus[<?= $fechaDia ?>][postre_id]"><?= opcionesPlato($todosPlatos, 'POSTRE', (int) $menuPrevio['postre_id']) ?></select></label>
          </div>
        </div>
      <?php endforeach; ?>

      <!-- Botones -->
      <div class="actions">
        <button type="submit" class="btn btn-primary btn-lg">
          💾 Guardar Semana
        </button>
        <button type="reset" class="btn btn-secondary">
          ↺ Limpiar cambios
        </button>
        <span style="font-size:.82rem;color:var(--muted);margin-left:.5rem;">
          Cada día necesita sopa, plato principal y postre del catálogo. Deja un día sin seleccionar para no publicarlo.
        </span>
      </div>

    </div>
  </form>

</div>
</body>
</html>
