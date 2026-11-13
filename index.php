<?php
require_once __DIR__ . '/backend/config/database.php';
require_once __DIR__ . '/backend/config/session.php';

$base = rtrim(BASE_URL, '/');

// Si ya está logueado, redirigir al panel correspondiente
if (isLoggedIn()) {
    $rol = rolActual();
    if ($rol === 'ADMIN') {
        header('Location: ' . $base . '/frontend/admin/dashboard.php');
    } else {
        header('Location: ' . $base . '/frontend/empleado/dashboard.php');
    }
    exit;
}

$error = htmlspecialchars($_GET['error'] ?? '', ENT_QUOTES, 'UTF-8');
$errorMsg = match ($error) {
    'acceso_denegado' => 'No tienes permiso para acceder a esa sección.',
    default => '',
};
?>
<!DOCTYPE html>
<html lang="es">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1.0">
  <title>Iniciar Sesión — Restaurante Rumbo al Cielo</title>
  <link rel="stylesheet" href="<?= $base ?>/frontend/assets/css/main.css">
  <style>
    body { background: linear-gradient(135deg, #154360 0%, #2e86c1 100%); }
  </style>
</head>
<body>
<div class="login-page">
  <div class="login-card">
    <div class="login-logo">
      <div class="logo-circle">🍽️</div>
      <h1>Rumbo al Cielo</h1>
      <p>Restaurante Institucional · Unión Boliviana</p>
    </div>

    <?php if ($errorMsg): ?>
      <div class="alert alert-danger"><?= $errorMsg ?></div>
    <?php endif; ?>

    <div id="loginAlert"></div>

    <form id="loginForm" novalidate>
      <div class="form-group">
        <label class="form-label" for="correo">Correo electrónico o documento</label>
        <input type="text" id="correo" name="correo" class="form-control"
               placeholder="correo@institucion.bo o CI" required autocomplete="username">
      </div>
      <div class="form-group">
        <label class="form-label" for="contrasena">Contraseña</label>
        <div style="position:relative;">
          <input type="password" id="contrasena" name="contrasena" class="form-control"
                 placeholder="••••••••" required autocomplete="current-password">
          <button type="button" id="togglePwd"
                  style="position:absolute;right:.6rem;top:50%;transform:translateY(-50%);
                         background:none;border:none;cursor:pointer;color:#6c757d;font-size:.9rem;">
            👁
          </button>
        </div>
      </div>
      <button type="submit" id="btnLogin" class="btn btn-primary w-100 btn-lg mt-2">
        Ingresar
      </button>
    </form>
    <p class="text-center mt-2"><a href="#" onclick="recuperarAcceso();return false;" style="color:#2e86c1;">¿Olvidaste tu contraseña?</a></p>
    <div id="recoveryAlert"></div>

    <p class="text-center text-muted mt-3" style="font-size:.8rem;">
      Sistema de Reservas de Almuerzos v1.0
    </p>
  </div>
</div>

<script>
  // BASE_URL disponible para app.js antes de que cargue
  const BASE_URL = <?= json_encode($base) ?>;
</script>
<script src="<?= $base ?>/frontend/assets/js/app.js"></script>
<script>
document.getElementById('togglePwd').addEventListener('click', function() {
  const input = document.getElementById('contrasena');
  input.type  = input.type === 'password' ? 'text' : 'password';
  this.textContent = input.type === 'password' ? '👁' : '🙈';
});

async function recuperarAcceso(){
  const identificador=prompt('Ingresa tu correo o CI registrado:');
  if(!identificador)return;
  const el=document.getElementById('recoveryAlert');
  el.innerHTML='<div class="alert alert-info">Verificando…</div>';
  const r=await apiPost('/backend/api/recuperar.php',{identificador:identificador.trim()});
  el.innerHTML='<div class="alert '+(r.success?'alert-success':'alert-danger')+'">'+(r.success?'✓ ':'⚠ ')+r.message+'</div>';
}

document.getElementById('loginForm').addEventListener('submit', async function(e) {
  e.preventDefault();
  const btn     = document.getElementById('btnLogin');
  const alertEl = document.getElementById('loginAlert');
  alertEl.innerHTML = '';
  btnLoading(btn, true);

  const res = await apiPost('/backend/api/auth.php', {
    action:     'login',
    correo:     document.getElementById('correo').value.trim(),
    contrasena: document.getElementById('contrasena').value,
  });

  if (res.success) {
    sessionStorage.removeItem('notifRecUltimo');
    alertEl.innerHTML = `<div class="alert alert-success">✓ ${res.message}</div>`;
    setTimeout(() => { window.location.href = res.redirect; }, 700);
  } else {
    alertEl.innerHTML = `<div class="alert alert-danger">⚠ ${res.message}</div>`;
    btnLoading(btn, false);
  }
});
</script>
</body>
</html>
