<?php
/**
 * Tarea programada diaria: convierte reservas no canceladas a INASISTENCIA
 * una vez alcanzada la hora límite configurada.
 * Ejecutar únicamente desde CLI (cron / Programador de tareas de Windows).
 */
if (PHP_SAPI !== 'cli') {
    http_response_code(403);
    exit("Acceso denegado. Ejecutar desde CLI.\n");
}
require_once __DIR__ . '/../config/database.php';
$db = Database::getConnection();
$horaLimite = '09:00';
$q = $db->prepare("SELECT valor FROM configuracion_sistema WHERE clave='hora_limite'");
$q->execute();
if ($valor = $q->fetchColumn()) $horaLimite = (string)$valor;
if (date('H:i') < $horaLimite) {
    echo "Aún no llega la hora de corte ({$horaLimite}).\n";
    exit(0);
}
$cerrar = $db->prepare("UPDATE reservas SET estado='INASISTENCIA' WHERE fecha_reserva=? AND estado='RESERVADO'");
$cerrar->execute([date('Y-m-d')]);
echo 'Reservas marcadas como inasistencia: ' . $cerrar->rowCount() . "\n";
