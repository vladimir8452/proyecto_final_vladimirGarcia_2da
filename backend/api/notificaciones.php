<?php
header('Content-Type: application/json; charset=utf-8');
require_once __DIR__ . '/../config/session.php';
require_once __DIR__ . '/../config/database.php';
ini_set('display_errors', '0');

requireAuth();
requireRole('ADMIN');

try {
    $db = Database::getConnection();
    $action = $_POST['action'] ?? $_GET['action'] ?? '';

    switch ($action) {
        case 'pendientes':
            $stmt = $db->query(
                "SELECT s.id, s.identificador, s.solicitado_en,
                        (SELECT u.nombre FROM usuarios u WHERE u.correo = s.identificador OR u.ci = s.identificador LIMIT 1) AS nombre,
                        (SELECT u.codigo_empleado FROM usuarios u WHERE u.correo = s.identificador OR u.ci = s.identificador LIMIT 1) AS codigo_empleado
                 FROM solicitudes_recuperacion s
                 WHERE s.estado = 'PENDIENTE'
                 ORDER BY s.solicitado_en DESC
                 LIMIT 50"
            );
            echo json_encode(['success' => true, 'data' => $stmt->fetchAll()]);
            break;

        case 'atender':
            $id = (int)($_POST['id'] ?? 0);
            $stmt = $db->prepare("UPDATE solicitudes_recuperacion SET estado = 'ATENDIDA' WHERE id = ?");
            $stmt->execute([$id]);
            echo json_encode(['success' => true, 'message' => 'Solicitud marcada como atendida.']);
            break;

        default:
            http_response_code(400);
            echo json_encode(['success' => false, 'message' => 'Acción no válida.']);
    }
} catch (Throwable $e) {
    error_log('[notificaciones.php] ' . $e->getMessage());
    echo json_encode(['success' => false, 'message' => 'Error interno.']);
}
