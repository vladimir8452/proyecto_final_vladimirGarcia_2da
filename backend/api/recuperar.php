<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/database.php';
ini_set('display_errors', '0');

try {
    $db = Database::getConnection();
    $identificador = trim($_POST['identificador'] ?? '');
    if ($identificador === '') {
        echo json_encode(['success' => false, 'message' => 'Ingresa tu correo o CI.']);
        exit;
    }

    $q = $db->prepare('SELECT id, nombre, activo FROM usuarios WHERE correo = ? OR ci = ? LIMIT 1');
    $q->execute([$identificador, $identificador]);
    $u = $q->fetch();

    if (!$u) {
        echo json_encode(['success' => false, 'message' => 'No encontramos ningún empleado con ese correo o CI. Verifica tus datos.']);
        exit;
    }
    if (!(int)$u['activo']) {
        echo json_encode(['success' => false, 'message' => 'Tu cuenta está suspendida o no autorizada. Comunícate con el administrador.']);
        exit;
    }

    $primerNombre = htmlspecialchars(strtok(trim($u['nombre']), ' '), ENT_QUOTES, 'UTF-8');

    // Si ya hay una solicitud pendiente de este empleado, no se duplica.
    $dup = $db->prepare("SELECT id FROM solicitudes_recuperacion WHERE identificador = ? AND estado = 'PENDIENTE' LIMIT 1");
    $dup->execute([$identificador]);
    if ($dup->fetch()) {
        echo json_encode(['success' => true, 'message' => "Empleado registrado: $primerNombre. Ya tienes una solicitud pendiente; el administrador te ayudará a restablecer tu contraseña."]);
        exit;
    }

    $ins = $db->prepare('INSERT INTO solicitudes_recuperacion (identificador) VALUES (?)');
    $ins->execute([$identificador]);

    // Aviso por correo (opcional: requiere SMTP configurado). La notificación principal aparece en el sistema.
    @mail('admin@rumboalcielo.bo', 'Solicitud de recuperación de contraseña',
          'El empleado ' . $u['nombre'] . ' (' . $identificador . ') solicitó recuperar su contraseña.',
          'From: sistema@rumboalcielo.bo');

    echo json_encode(['success' => true, 'message' => "Empleado registrado: $primerNombre. Se envió una notificación al administrador; la verá al ingresar al sistema y te ayudará a restablecer tu contraseña."]);
} catch (Throwable $e) {
    error_log('[recuperar.php] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'No se pudo procesar la solicitud. Intenta nuevamente.']);
}
